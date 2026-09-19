<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;

/**
 * Checks that the variables a template declares are provided by the action it renders for.
 *
 * A template is paired with an action when their paths sit in sibling roots: the same depth, differing in
 * exactly one segment, which is the directory convention the framework documents. A template with no such
 * action is not checked, so partials, layouts, elements and error templates are left alone.
 *
 * The variables may come from the action's returned data or from the application's {@see ShareInterface}.
 * When the paired template is the only one the action can render, every return must provide them; when the
 * action selects other templates too, each has to be reachable from some return.
 *
 * @implements Rule<CollectedDataNode>
 */
final class TemplateVariablesRule implements Rule
{
    /** An action file: `<...>/<route path>.<method>.php`. */
    private const ACTION_FILE = '#^(?<route>.*)\.(?:get|post|put|patch|delete|head|options)\.php$#';

    /** A template file: `<...>/<route path>.html.php`. */
    private const TEMPLATE_FILE = '#^(?<route>.*)\.html\.php$#';

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $actions = $node->get(ActionDataCollector::class);
        $selections = $node->get(TemplateSelectionCollector::class);
        $shared = self::sharedVariables($node->get(ShareCollector::class));
        $errors = [];

        foreach ($node->get(TemplateVariablesCollector::class) as $templateFile => $entries) {
            $route = self::match(self::TEMPLATE_FILE, $templateFile);
            if ($route === null) {
                continue;
            }

            $declared = [];
            foreach ($entries as $entry) {
                $declared = [...$declared, ...$entry];
            }
            $wanted = \array_diff_key($declared, \array_flip($shared));
            if ($wanted === []) {
                continue;
            }

            foreach (self::paired($route, $actions) as $actionFile => $returns) {
                foreach (self::check($actionFile, $returns, $selections[$actionFile] ?? [], $route, $templateFile, $wanted) as $error) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * @param list<array{line: int, variants: array<int, string[]>|null}> $returns
     * @param list<array{line: int, templates: string[]|null}> $selections
     * @param array<string, array{type: Type, line: int}> $wanted
     * @return list<IdentifierRuleError>
     */
    private static function check(
        string $actionFile,
        array $returns,
        array $selections,
        string $templateRoute,
        string $templateFile,
        array $wanted,
    ): array {
        $errors = [];
        $only = true;
        foreach ($selections as $selection) {
            if ($selection['templates'] === null) {
                $errors[] = self::error(
                    'setTemplate() needs a literal or constant template name, so that the templates an action renders'
                    . ' can be checked.',
                    $actionFile,
                    $selection['line'],
                );
                continue;
            }
            foreach ($selection['templates'] as $template) {
                if (! \str_ends_with($templateRoute, '/' . $template)) {
                    $only = false;
                }
            }
        }

        $variants = [];
        foreach ($returns as $return) {
            if ($return['variants'] === null) {
                $errors[] = self::error(
                    'The data this return produces has no statically known keys, so the paired template cannot be'
                    . ' checked. Return an array shape, or a constant array.',
                    $actionFile,
                    $return['line'],
                );
                continue;
            }
            foreach ($return['variants'] as $variant) {
                $variants[] = ['keys' => $variant, 'line' => $return['line']];
            }
        }

        if ($only) {
            foreach ($variants as $variant) {
                $errors = [
                    ...$errors,
                    ...self::missing(
                        \array_diff_key($wanted, \array_flip($variant['keys'])),
                        $actionFile,
                        $variant['line'],
                        \sprintf('This return does not provide $%%s, which the template %s declares.', $templateFile),
                    ),
                ];
            }
            return $errors;
        }

        foreach ($wanted as $name => $declaration) {
            foreach ($variants as $variant) {
                if (\in_array($name, $variant['keys'], true)) {
                    continue 2;
                }
            }
            $errors[] = self::error(
                \sprintf('No return of this action provides $%s, which the template %s declares.', $name, $templateFile),
                $templateFile,
                $declaration['line'],
            );
        }

        return $errors;
    }

    /**
     * @param array<string, array{type: Type, line: int}> $missing
     * @return list<IdentifierRuleError>
     */
    private static function missing(array $missing, string $file, int $line, string $message): array
    {
        $errors = [];
        foreach (\array_keys($missing) as $name) {
            $errors[] = self::error(\sprintf($message, $name), $file, $line);
        }
        return $errors;
    }

    /**
     * @param array<string, list<array{class: string, variables: string[]}>> $shares
     * @return string[]
     */
    private static function sharedVariables(array $shares): array
    {
        $shared = [];
        foreach ($shares as $entries) {
            foreach ($entries as $entry) {
                // The framework's own default is not an application's share.
                if (\str_starts_with($entry['class'], 'SubstancePHP\\HTTP\\')) {
                    continue;
                }
                $shared = [...$shared, ...$entry['variables']];
            }
        }
        return \array_values(\array_unique($shared));
    }

    /**
     * @param array<string, list<array{line: int, variants: array<int, string[]>|null}>> $actions
     * @return array<string, list<array{line: int, variants: array<int, string[]>|null}>>
     */
    private static function paired(string $templateRoute, array $actions): array
    {
        $paired = [];
        $segments = \explode('/', $templateRoute);
        foreach ($actions as $actionFile => $returns) {
            $actionRoute = self::match(self::ACTION_FILE, $actionFile);
            if ($actionRoute !== null && self::siblingRoots($segments, \explode('/', $actionRoute))) {
                $paired[$actionFile] = $returns;
            }
        }
        return $paired;
    }

    /**
     * Two routes sit in sibling roots when they have the same depth and differ in exactly one segment, which
     * is the root directory (e.g. `actions/` and `templates/`).
     *
     * @param string[] $left
     * @param string[] $right
     */
    private static function siblingRoots(array $left, array $right): bool
    {
        if (\count($left) !== \count($right)) {
            return false;
        }
        $differences = 0;
        foreach ($left as $index => $segment) {
            if ($segment !== $right[$index]) {
                $differences++;
            }
        }
        return ($differences === 1);
    }

    private static function match(string $pattern, string $path): ?string
    {
        return (\preg_match($pattern, $path, $matches) === 1) ? $matches['route'] : null;
    }

    private static function error(string $message, string $file, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->file($file)
            ->line($line)
            ->identifier('substancephp.templateVariable')
            ->build();
    }
}
