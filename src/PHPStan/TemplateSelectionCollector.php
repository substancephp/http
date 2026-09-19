<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Every `setTemplate()` call and the templates it selects, so an action's template set is known.
 *
 * A call whose argument is not a literal or constant reports `null` templates: the selection cannot be
 * checked, which the pairing rule reports rather than ignoring.
 *
 * @implements Collector<MethodCall, array{line: int, templates: string[]|null}>
 */
final class TemplateSelectionCollector implements Collector
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return array{line: int, templates: string[]|null}|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $node->name instanceof Identifier || $node->name->toString() !== 'setTemplate') {
            return null;
        }
        $argument = $node->getArgs()[0] ?? null;
        if ($argument === null) {
            return ['line' => $node->getStartLine(), 'templates' => null];
        }

        $templates = [];
        foreach ($scope->getType($argument->value)->getConstantStrings() as $constant) {
            $templates[] = $constant->getValue();
        }

        return [
            'line' => $node->getStartLine(),
            'templates' => ($templates === []) ? null : $templates,
        ];
    }
}
