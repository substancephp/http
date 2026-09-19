<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Checks that the variables a template declares are provided by the action it renders for.
 *
 * A template is paired with an action when their paths sit in sibling roots: the same depth, differing in
 * exactly one segment, which is the directory convention the framework documents. A template with no such
 * action is not checked, so partials, layouts, elements and error templates are left alone.
 *
 * {@see DefaultTemplate} moves an action's default off that conventional path, and {@see AltTemplates} adds
 * templates it may also render, so those are resolved by the names they declare.
 *
 * The variables may come from the action's returned data or from the application's {@see ShareInterface}.
 * When a template is the only one the action can render, every return must provide them; when the action
 * renders others too, each template has to be reachable from some return.
 *
 * @implements Rule<CollectedDataNode>
 */
final class TemplateVariablesRule implements Rule
{
    /** An action file: `<...>/<route path>.<method>.php`. */
    private const ACTION_FILE = '#^(?<route>.*)\.(?:get|post|put|patch|delete|head|options)\.php$#';

    /** A template file: `<...>/<route path>.html.php`. */
    private const TEMPLATE_FILE = '#^(?<route>.*)\.html\.php$#';

    public function __construct(private TypeStringResolver $typeResolver)
    {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $actions = $node->get(ActionDataCollector::class);
        $selections = $node->get(TemplateSelectionCollector::class);
        $declarations = $node->get(TemplateSetCollector::class);
        $shared = self::sharedVariables($node->get(ShareCollector::class));

        $templates = [];
        foreach ($node->get(TemplateVariablesCollector::class) as $templateFile => $entries) {
            $route = self::match(self::TEMPLATE_FILE, $templateFile);
            if ($route === null) {
                continue;
            }
            foreach ($entries as $entry) {
                $templates[$route]['file'] = $templateFile;
                $templates[$route]['declared'] = [...($templates[$route]['declared'] ?? []), ...$entry];
            }
        }

        $errors = [];
        foreach ($templates as $route => $template) {
            $wanted = self::wanted($template['declared'], $shared);
            if ($wanted === []) {
                continue;
            }
            foreach (self::paired($route, $actions) as $actionFile => $returns) {
                $declaration = self::declaration($declarations, $actionFile);
                if ($declaration !== null
                    && $declaration['default'] !== null
                    && ! \str_ends_with($route, '/' . $declaration['default'])
                ) {
                    continue;
                }
                $errors = [
                    ...$errors,
                    ...$this->check(
                        $actionFile,
                        $returns,
                        $selections[$actionFile] ?? [],
                        $declaration,
                        $route,
                        $template['file'],
                        $wanted,
                    ),
                ];
            }
        }

        foreach ($declarations as $actionFile => $entries) {
            $declaration = $entries[0] ?? null;
            if ($declaration === null) {
                continue;
            }
            if ($declaration['unreadable']) {
                $errors[] = self::error(
                    'The template attributes here need literal or constant template names, so that the templates this'
                    . ' action renders can be checked.',
                    $actionFile,
                    $declaration['line'],
                );
            }

            $targets = $declaration['alternatives'];
            if ($declaration['default'] !== null) {
                $targets[] = $declaration['default'];
            }
            $actionRoute = self::match(self::ACTION_FILE, $actionFile) ?? '';
            foreach (\array_unique($targets) as $target) {
                if (\str_ends_with($actionRoute, '/' . $target)) {
                    // The conventionally named template is already checked from the template's own side.
                    continue;
                }
                $matches = self::matching($target, $templates);
                if ($matches === []) {
                    $errors[] = self::error(
                        \sprintf('This action declares the template %s, which no analysed template file provides.', $target),
                        $actionFile,
                        $declaration['line'],
                    );
                    continue;
                }
                foreach ($matches as $route => $template) {
                    $wanted = self::wanted($template['declared'], $shared);
                    if ($wanted === []) {
                        continue;
                    }
                    $errors = [
                        ...$errors,
                        ...$this->check(
                            $actionFile,
                            $actions[$actionFile] ?? [],
                            $selections[$actionFile] ?? [],
                            $declaration,
                            $route,
                            $template['file'],
                            $wanted,
                        ),
                    ];
                }
            }
        }

        $this->checkIncludeSites($node, $templates, $shared, $errors);

        return $errors;
    }

    /**
     * Checks the renderer helpers that include another template, each of which resolves its name inside its
     * own directory: what a call passes has to satisfy what the template it names declares, less the
     * variables the application's share provides.
     *
     * @param array<string, array{file: string, declared: array<string, array{type: string, line: int}>}> $templates
     * @param string[] $shared
     * @param list<IdentifierRuleError> $errors
     */
    private function checkIncludeSites(
        CollectedDataNode $node,
        array $templates,
        array $shared,
        array &$errors,
    ): void {
        foreach ($node->get(IncludeSiteCollector::class) as $file => $sites) {
            if (! isset($templates[self::route($file)])) {
                // Includes only render inside a template; test code calls the renderer helpers directly.
                continue;
            }
            foreach ($sites as $site) {
                if ($site['template'] === null) {
                    $errors[] = self::error(
                        'The template this includes is not a literal or constant, so what it needs cannot be checked.',
                        $file,
                        $site['line'],
                    );
                    continue;
                }

                $matches = self::matching($site['template'], $templates);
                if ($matches === []) {
                    $errors[] = self::error(
                        \sprintf('This includes %s, which no analysed template file provides.', $site['template']),
                        $file,
                        $site['line'],
                    );
                    continue;
                }

                foreach ($matches as $template) {
                    foreach (self::wanted($template['declared'], $shared) as $variable => $declaration) {
                        if (! \array_key_exists($variable, $site['provides'])) {
                            $errors[] = self::error(
                                \sprintf('This does not pass $%s, which %s declares.', $variable, $template['file']),
                                $file,
                                $site['line'],
                            );
                            continue;
                        }
                        $provided = $site['provides'][$variable];
                        if ($this->accepts($declaration['type'], $provided) === false) {
                            $errors[] = self::error(
                                \sprintf(
                                    'This passes $%s as %s, but %s declares %s.',
                                    $variable,
                                    $provided,
                                    $template['file'],
                                    $declaration['type'],
                                ),
                                $file,
                                $site['line'],
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array<string, list<array{line: int, default: string|null, alternatives: string[], unreadable: bool}>> $declarations
     * @return array{line: int, default: string|null, alternatives: string[], unreadable: bool}|null
     */
    private static function declaration(array $declarations, string $actionFile): ?array
    {
        return $declarations[$actionFile][0] ?? null;
    }

    /**
     * @param array<string, array{file: string, declared: array<string, array{type: string, line: int}>}> $templates
     * @return array<string, array{file: string, declared: array<string, array{type: string, line: int}>}>
     */
    private static function matching(string $template, array $templates): array
    {
        $matching = [];
        foreach ($templates as $route => $declaration) {
            if (\str_ends_with($route, '/' . $template)) {
                $matching[$route] = $declaration;
            }
        }
        return $matching;
    }

    /**
     * @param array<string, array{type: string, line: int}> $declared
     * @param string[] $shared
     * @return array<string, array{type: string, line: int}>
     */
    private static function wanted(array $declared, array $shared): array
    {
        return \array_diff_key($declared, \array_flip($shared));
    }

    /**
     * @param list<array{line: int, variants: array<int, array<string, string>>|null}> $returns
     * @param list<array{line: int, templates: string[]|null}> $selections
     * @param array{line: int, default: string|null, alternatives: string[], unreadable: bool}|null $declaration
     * @param array<string, array{type: string, line: int}> $wanted
     * @return list<IdentifierRuleError>
     */
    private function check(
        string $actionFile,
        array $returns,
        array $selections,
        ?array $declaration,
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
        foreach ($declaration['alternatives'] ?? [] as $alternative) {
            if (! \str_ends_with($templateRoute, '/' . $alternative)) {
                $only = false;
            }
        }
        $default = $declaration['default'] ?? null;
        if ($default !== null && ! \str_ends_with($templateRoute, '/' . $default)) {
            $only = false;
        }
        $actionRoute = self::match(self::ACTION_FILE, $actionFile);
        $isConventional = $actionRoute !== null
            && self::siblingRoots(\explode('/', $templateRoute), \explode('/', $actionRoute));
        if ($default === null && ! $isConventional) {
            // The action also renders the template its route resolves to.
            $only = false;
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
                foreach ($wanted as $name => $wantedDeclaration) {
                    if (! \array_key_exists($name, $variant['keys'])) {
                        $errors[] = self::error(
                            \sprintf('This return does not provide $%s, which the template %s declares.', $name, $templateFile),
                            $actionFile,
                            $variant['line'],
                        );
                        continue;
                    }
                    $provided = $variant['keys'][$name];
                    if ($this->accepts($wantedDeclaration['type'], $provided) === false) {
                        $errors[] = self::error(
                            \sprintf(
                                'This return provides $%s as %s, but the template %s declares %s.',
                                $name,
                                $provided,
                                $templateFile,
                                $wantedDeclaration['type'],
                            ),
                            $actionFile,
                            $variant['line'],
                        );
                    }
                }
            }
            return $errors;
        }

        // A template is renderable when a single return provides everything it declares.
        foreach ($variants as $variant) {
            if ($this->satisfies($variant['keys'], $wanted)) {
                return $errors;
            }
        }

        $absent = false;
        foreach ($wanted as $name => $wantedDeclaration) {
            foreach ($variants as $variant) {
                if (\array_key_exists($name, $variant['keys'])) {
                    continue 2;
                }
            }
            $absent = true;
            $errors[] = self::error(
                \sprintf('No return of this action provides $%s, which the template %s declares.', $name, $templateFile),
                $templateFile,
                $wantedDeclaration['line'],
            );
        }
        if ($absent) {
            return $errors;
        }

        // Every variable is provided by some return, but never all of them by one, so nothing renders this.
        $errors[] = self::error(
            \sprintf(
                'No return of this action provides %s together, which the template %s declares.',
                \implode(', ', \array_map(static fn (string $name): string => '$' . $name, \array_keys($wanted))),
                $templateFile,
            ),
            $templateFile,
            \array_values($wanted)[0]['line'],
        );

        return $errors;
    }

    /**
     * Whether one return provides everything a template declares, with types it accepts.
     *
     * @param array<string, string> $keys
     * @param array<string, array{type: string, line: int}> $wanted
     */
    private function satisfies(array $keys, array $wanted): bool
    {
        foreach ($wanted as $name => $declaration) {
            if (! \array_key_exists($name, $keys)) {
                return false;
            }
            if ($this->accepts($declaration['type'], $keys[$name]) === false) {
                return false;
            }
        }
        return true;
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
     * @param array<string, list<array{line: int, variants: array<int, array<string, string>>|null}>> $actions
     * @return array<string, list<array{line: int, variants: array<int, array<string, string>>|null}>>
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

    /** The route a file path stands for, whether or not it is a template. */
    private static function route(string $path): string
    {
        return self::match(self::TEMPLATE_FILE, $path) ?? $path;
    }

    /**
     * Whether what an action provides satisfies the type a template declares, both given as type
     * descriptions because collected data crosses files as JSON.
     *
     * `null` means no decision was reached, either because a description could not be resolved or because
     * PHPStan cannot prove the relation either way, such as a `mixed` provided value. A pair left undecided
     * is not reported, so the check only speaks when it is sure.
     */
    private function accepts(string $declared, string $provided): ?bool
    {
        try {
            $result = $this->typeResolver->resolve($declared)
                ->isSuperTypeOf($this->typeResolver->resolve($provided));
        } catch (\Throwable) {
            return null;
        }
        if ($result->yes()) {
            return true;
        }
        return $result->no() ? false : null;
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
