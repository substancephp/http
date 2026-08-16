<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;

/**
 * Flags output of unescaped content via functions that write to the output
 * stream in HTML templates: `printf()`, `vprintf()`, `var_dump()` and
 * `print_r()`. Other function calls are ignored here; if their result is
 * output, the `echo`/`<?= ?>` or `print` rule reports it instead.
 *
 * A `printf`/`vprintf`, `var_dump` or `print_r` invoked dynamically is
 * reported only when PHPStan can resolve the callee to the constant string
 * name of an output function (a variable typed as that name, a
 * single-element callable array such as `['printf']`, or
 * `call_user_func`/`call_user_func_array` with such a first argument);
 * otherwise the call is skipped.
 *
 * @implements Rule<FuncCall>
 */
final class UnescapedOutputFuncCallRule implements Rule
{
    #[\Override]
    /** @return class-string<FuncCall> */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    #[\Override]
    /** @param FuncCall $node */
    public function processNode(Node $node, Scope $scope): array
    {
        $checker = new UnescapedOutputChecker();
        if (! $node->name instanceof Name) {
            if ($this->isOutputFunctionName($scope->getType($node->name))) {
                return $checker->checkExpressions([$node], $scope);
            }

            return [];
        }

        $name = \strtolower($node->name->toString());
        switch ($name) {
            case 'printf':
            case 'vprintf':
            case 'var_dump':
                return $checker->checkExpressions([$node], $scope);
            case 'print_r':
                // print_r($x, true) returns a string instead of printing it.
                $secondArg = $node->args[1] ?? null;
                if ($secondArg instanceof Arg && $scope->getType($secondArg->value)->isTrue()->yes()) {
                    return [];
                }

                return $checker->checkExpressions([$node], $scope);
            case 'call_user_func':
            case 'call_user_func_array':
                $firstArg = $node->args[0] ?? null;
                if (
                    $firstArg instanceof Arg
                    && $this->isOutputFunctionName($scope->getType($firstArg->value))
                ) {
                    return $checker->checkExpressions([$node], $scope);
                }

                return [];
            default:
                return [];
        }
    }

    private function isOutputFunctionName(Type $type): bool
    {
        foreach ($type->getConstantStrings() as $constantString) {
            if ($this->isOutputFunctionNameValue($constantString->getValue())) {
                return true;
            }
        }
        // Callable arrays, e.g. $fn = ['printf'].
        foreach ($type->getConstantArrays() as $array) {
            $valueTypes = $array->getValueTypes();
            if (\count($valueTypes) === 1) {
                foreach ($valueTypes[0]->getConstantStrings() as $constantString) {
                    if ($this->isOutputFunctionNameValue($constantString->getValue())) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function isOutputFunctionNameValue(string $name): bool
    {
        $name = \strtolower($name);

        return $name === 'printf' || $name === 'vprintf' || $name === 'var_dump' || $name === 'print_r';
    }
}
