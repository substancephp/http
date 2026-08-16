<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * Classifies the expressions that a template outputs via `echo`, `<?= ?>`,
 * `print`, `die()`/`exit()`, `printf()` or `vprintf()`, and reports any that
 * are not escaped.
 *
 * The rules only apply inside HTML templates, i.e. files where `$this` is
 * typed as {@see HtmlRenderer} (via a `@var HtmlRenderer $this` docblock, as
 * the library's own templates do). A template that lacks this annotation is
 * not checked, so the annotation is mandatory for the rules to have effect.
 *
 * Output is considered safe when it is:
 *
 * - a string, number or magic-constant literal, or the booleans `true`,
 *   `false` and `null`;
 * - the result of an escaping method of {@see HtmlRenderer}: `h()`, `e()`,
 *   `escapeHtml()`, `escapeHtmlAttr()`, `escapeJs()`, `escapeCss()` or
 *   `escapeUrl()`;
 * - a call to PHP's `htmlspecialchars()` or `htmlentities()`;
 * - a concatenation, ternary or null-coalescing expression, or an
 *   interpolated string, made only of safe parts;
 * - a `printf()`/`vprintf()` call with a literal format string whose
 *   interpolated arguments are all safe.
 *
 * A call to `raw()` is the explicit escape hatch: the template author opts
 * out of escaping by calling it, so its output is always permitted, without
 * any additional annotation. Only content known to be free of user input
 * should pass through it.
 *
 * Everything else is reported as unescaped output.
 *
 * Note that the rules only check that some escaping happened, not that the
 * escaper matches the surrounding HTML context: `escapeHtml()` is safe in
 * text content, but inside an attribute `escapeHtmlAttr()` is required, and
 * so on. Choose the escaper that matches the context.
 *
 * @internal
 */
final class UnescapedOutputChecker
{
    /** Escape methods of {@see HtmlRenderer}; output of these is safe. */
    private const ESCAPE_METHODS = [
        'h' => true,
        'e' => true,
        'escapehtml' => true,
        'escapehtmlattr' => true,
        'escapejs' => true,
        'escapecss' => true,
        'escapeurl' => true,
    ];

    /** Built-in functions that escape HTML text content; output is safe. */
    private const ESCAPE_FUNCTIONS = [
        'htmlspecialchars' => true,
        'htmlentities' => true,
    ];

    private const SAFE = 'safe';
    private const RAW = 'raw';
    private const UNSAFE = 'unsafe';

    /**
     * @param array<Expr> $exprs
     * @return list<IdentifierRuleError>
     */
    public function checkExpressions(array $exprs, Scope $scope): array
    {
        if (! $this->isHtmlTemplate($scope)) {
            return [];
        }

        $errors = [];
        foreach ($exprs as $expr) {
            if ($this->classify($expr, $scope) !== self::UNSAFE) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(
                'Output is not escaped, which risks XSS. Escape it with $this->h() or '
                . '$this->escapeHtml() (or another escape method), or mark it as intentionally '
                . 'unescaped with $this->raw().',
            )->identifier('substancephp.unescapedOutput')->build();
        }

        return $errors;
    }

    private function isHtmlTemplate(Scope $scope): bool
    {
        return $this->isRendererObject($scope->getType(new Variable('this')));
    }

    private function isRendererObject(Type $type): bool
    {
        return (new ObjectType(HtmlRenderer::class))->isSuperTypeOf($type)->yes();
    }

    private function classify(Expr $expr, Scope $scope): string
    {
        if ($expr instanceof String_
            || $expr instanceof LNumber
            || $expr instanceof DNumber
            || $expr instanceof MagicConst
        ) {
            return self::SAFE;
        }

        if ($expr instanceof ConstFetch) {
            $name = \strtolower($expr->name->toString());
            if ($name === 'true' || $name === 'false' || $name === 'null') {
                return self::SAFE;
            }
        }

        if ($expr instanceof Concat) {
            return $this->combine(
                $this->classify($expr->left, $scope),
                $this->classify($expr->right, $scope),
            );
        }

        if ($expr instanceof Ternary) {
            $if = $expr->if ?? $expr->cond;
            return $this->combine(
                $this->classify($if, $scope),
                $this->classify($expr->else, $scope),
            );
        }

        if ($expr instanceof Coalesce) {
            return $this->combine(
                $this->classify($expr->left, $scope),
                $this->classify($expr->right, $scope),
            );
        }

        if ($expr instanceof InterpolatedString) {
            $result = self::SAFE;
            foreach ($expr->parts as $part) {
                if (! $part instanceof Expr) {
                    continue;
                }
                $verdict = $this->classify($part, $scope);
                if ($verdict === self::UNSAFE) {
                    return self::UNSAFE;
                }
                if ($verdict === self::RAW) {
                    $result = self::RAW;
                }
            }

            return $result;
        }

        if ($expr instanceof MethodCall) {
            return $this->classifyMethodCall($expr, $scope);
        }

        if ($expr instanceof FuncCall) {
            return $this->classifyFuncCall($expr, $scope);
        }

        // Output of a definite integer, float or boolean cannot contain
        // markup, so it is safe to print without escaping.
        $type = $scope->getType($expr);
        if ($type->isInteger()->yes() || $type->isFloat()->yes() || $type->isBoolean()->yes()) {
            return self::SAFE;
        }

        return self::UNSAFE;
    }

    private function classifyMethodCall(MethodCall $call, Scope $scope): string
    {
        if (! $this->isRendererObject($scope->getType($call->var))) {
            return self::UNSAFE;
        }
        if (! $call->name instanceof Identifier) {
            return self::UNSAFE;
        }
        $name = \strtolower($call->name->toString());
        if (isset(self::ESCAPE_METHODS[$name])) {
            return self::SAFE;
        }
        if ($name === 'raw') {
            return self::RAW;
        }

        return self::UNSAFE;
    }

    private function classifyFuncCall(FuncCall $call, Scope $scope): string
    {
        if (! $call->name instanceof Name) {
            return self::UNSAFE;
        }
        $name = \strtolower($call->name->toString());
        if (isset(self::ESCAPE_FUNCTIONS[$name])) {
            return self::SAFE;
        }
        if ($name === 'printf' || $name === 'vprintf') {
            return $this->classifyPrintfCall($call, $name, $scope);
        }

        return self::UNSAFE;
    }

    private function classifyPrintfCall(FuncCall $call, string $name, Scope $scope): string
    {
        $args = $call->args;
        if ($args === []) {
            // No format string to inspect.
            return self::UNSAFE;
        }
        if (! $args[0] instanceof Arg || $this->classify($args[0]->value, $scope) !== self::SAFE) {
            // Format string is not a constant; the output cannot be checked.
            return self::UNSAFE;
        }

        $rest = \array_slice($args, 1);
        if ($rest === []) {
            // Constant format string with nothing interpolated.
            return self::SAFE;
        }

        if ($name === 'vprintf') {
            if (! $rest[0] instanceof Arg) {
                return self::UNSAFE;
            }

            return $this->classifyVprintfArgs($rest[0], $scope);
        }

        $result = self::SAFE;
        foreach ($rest as $arg) {
            if (! $arg instanceof Arg) {
                return self::UNSAFE;
            }
            $verdict = $this->classify($arg->value, $scope);
            if ($verdict === self::UNSAFE) {
                return self::UNSAFE;
            }
            if ($verdict === self::RAW) {
                $result = self::RAW;
            }
        }

        return $result;
    }

    private function classifyVprintfArgs(Arg $arg, Scope $scope): string
    {
        $value = $arg->value;
        if (! $value instanceof Array_) {
            return self::UNSAFE;
        }

        $result = self::SAFE;
        foreach ($value->items as $item) {
            $verdict = $this->classify($item->value, $scope);
            if ($verdict === self::UNSAFE) {
                return self::UNSAFE;
            }
            if ($verdict === self::RAW) {
                $result = self::RAW;
            }
        }

        return $result;
    }

    private function combine(string $a, string $b): string
    {
        if ($a === self::UNSAFE || $b === self::UNSAFE) {
            return self::UNSAFE;
        }
        if ($a === self::RAW || $b === self::RAW) {
            return self::RAW;
        }

        return self::SAFE;
    }
}
