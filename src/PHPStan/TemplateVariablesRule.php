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
        $actions = self::callbackReturns(
            $node->get(ActionDataCollector::class),
            $node->get(ClosureSpanCollector::class),
        );
        $selections = $node->get(TemplateSelectionCollector::class);
        $declarations = $node->get(TemplateSetCollector::class);
        $shared = self::sharedVariables(
            $node->get(SharePropertyCollector::class),
            $node->get(ShareParameterCollector::class),
        );

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
            foreach ($this->sharedTypeMismatches($template, $shared) as $error) {
                $errors[] = $error;
            }
            foreach ($this->errorTemplateMismatches($template, $route, $shared) as $error) {
                $errors[] = $error;
            }
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
     * @param array<string, string[]> $shared
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

                if (! $site['readable']) {
                    $errors[] = self::error(
                        'The data this include passes is not statically known, so the template it names cannot'
                        . ' be checked. Pass an array literal.',
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
     * The variables a template takes from the application's share but declares with a type the share does not
     * publish. Where several shares publish the same variable, any one of them satisfying the declaration is
     * enough, so a test double cannot produce a false positive.
     *
     * @param array{file: string, declared: array<string, array{type: string, line: int}>} $template
     * @param array<string, string[]> $shared
     * @return list<IdentifierRuleError>
     */
    private function sharedTypeMismatches(array $template, array $shared): array
    {
        $errors = [];
        foreach ($template['declared'] as $name => $declaration) {
            $published = $shared[$name] ?? [];
            if ($published === []) {
                continue;
            }
            foreach ($published as $type) {
                if ($this->accepts($declaration['type'], $type) === true) {
                    continue 2;
                }
            }
            $errors[] = self::error(
                \sprintf(
                    'The template %s declares $%s as %s, but the application share publishes %s.',
                    $template['file'],
                    $name,
                    $declaration['type'],
                    \implode(' or ', $published),
                ),
                $template['file'],
                $declaration['line'],
            );
        }
        return $errors;
    }

    /**
     * The variables an error page is rendered with, as the exception handler builds them. An error page has
     * no action to take data from, so this is the whole of what it can declare, share aside.
     */
    private const ERROR_PAGE_DATA = ['error' => 'string', 'statusCode' => 'int'];

    /**
     * The variables an error page template declares that the framework does not render it with.
     *
     * Error templates are recognised by their route: the documented default root, `error`, with an optional
     * status code, as `error/422`. An application that configures a different root is not checked.
     *
     * @param array{file: string, declared: array<string, array{type: string, line: int}>} $template
     * @param array<string, string[]> $shared
     * @return list<IdentifierRuleError>
     */
    private function errorTemplateMismatches(array $template, string $route, array $shared): array
    {
        if (\preg_match('#/error(/\d+)?$#', $route) !== 1) {
            return [];
        }

        $errors = [];
        foreach (self::wanted($template['declared'], $shared) as $name => $declaration) {
            $provided = self::ERROR_PAGE_DATA[$name] ?? null;
            if ($provided === null) {
                $errors[] = self::error(
                    \sprintf(
                        'The error page template %s declares $%s, which the framework does not provide it.',
                        $template['file'],
                        $name,
                    ),
                    $template['file'],
                    $declaration['line'],
                );
                continue;
            }
            if ($this->accepts($declaration['type'], $provided) === false) {
                $errors[] = self::error(
                    \sprintf(
                        'The error page template %s declares $%s as %s, but the framework provides %s.',
                        $template['file'],
                        $name,
                        $declaration['type'],
                        $provided,
                    ),
                    $template['file'],
                    $declaration['line'],
                );
            }
        }
        return $errors;
    }

    /**
     * @param array<string, array{type: string, line: int}> $declared
     * @param array<string, string[]> $shared
     * @return array<string, array{type: string, line: int}>
     */
    private static function wanted(array $declared, array $shared): array
    {
        return \array_diff_key($declared, $shared);
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

        if ($variants === []) {
            // Every return in this action is bodyless, so no template renders and there is nothing to check.
            return $errors;
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
     * Every variable the application's shares publish, with the types they publish it as.
     *
     * Several implementations are unioned, so a variable any of them publishes counts as provided.
     *
     * @param array<string, list<list<array{variable: string, type: string}>>> ...$sources
     * @return array<string, string[]>
     */
    private static function sharedVariables(array ...$sources): array
    {
        $shared = [];
        foreach ($sources as $files) {
            foreach ($files as $nodes) {
                foreach ($nodes as $variables) {
                    foreach ($variables as $variable) {
                        $shared[$variable['variable']][] = $variable['type'];
                    }
                }
            }
        }
        foreach ($shared as $variable => $types) {
            $shared[$variable] = \array_values(\array_unique($types));
        }
        return $shared;
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
     * Keeps only the returns belonging to the action callback, dropping those of helper closures declared
     * inside it.
     *
     * The callback is the closure the action file returns at top level, and a nested closure is one whose span
     * sits strictly inside it, which is how they are told apart: a collector cannot see which function it is
     * in, and an anonymous function's scope reports no function name.
     *
     * @param array<string, list<array{line: int, variants: array<int, array<string, string>>|null, callback?: array{start: int, end: int}}>> $actions
     * @param array<string, list<array{start: int, end: int}>> $closures
     * @return array<string, list<array{line: int, variants: array<int, array<string, string>>|null}>>
     */
    private static function callbackReturns(array $actions, array $closures): array
    {
        $kept = [];
        foreach ($actions as $file => $returns) {
            $callback = null;
            foreach ($returns as $return) {
                if (isset($return['callback'])) {
                    $callback = $return['callback'];
                }
            }
            if ($callback === null) {
                continue;
            }

            $nested = [];
            foreach ($closures[$file] ?? [] as $closure) {
                if (($closure['start'] > $callback['start']) && ($closure['end'] < $callback['end'])) {
                    $nested[] = $closure;
                }
            }

            foreach ($returns as $return) {
                if (! self::inside($return['line'], $callback)) {
                    continue;
                }
                foreach ($nested as $span) {
                    if (self::inside($return['line'], $span)) {
                        continue 2;
                    }
                }
                $kept[$file][] = ['line' => $return['line'], 'variants' => $return['variants']];
            }
        }
        return $kept;
    }

    /** @param array{start: int, end: int} $span */
    private static function inside(int $line, array $span): bool
    {
        return ($line >= $span['start']) && ($line <= $span['end']);
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
