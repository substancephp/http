<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * The keys a single `return` statement in an action provides, one entry per possible shape.
 *
 * A return whose type has no statically known keys (an untyped `array`, `mixed`) reports `null` variants,
 * which the pairing rule treats as unverifiable rather than as providing nothing.
 *
 * @implements Collector<Node\Stmt\Return_, array{line: int, variants: array<int, string[]>|null}>
 */
final class ActionDataCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Stmt\Return_::class;
    }

    /** @return array{line: int, variants: array<int, string[]>|null}|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        // Action files return their callback at the top level; only returns inside it are response data.
        if (! $scope->isInAnonymousFunction()) {
            return null;
        }

        // A bare `return`, or one returning null (a bodyless response), provides no variables.
        if ($node->expr === null) {
            return ['line' => $node->getStartLine(), 'variants' => [[]]];
        }

        $type = $scope->getType($node->expr);
        if ($type->isNull()->yes()) {
            return ['line' => $node->getStartLine(), 'variants' => [[]]];
        }

        $variants = [];
        foreach ($type->getConstantArrays() as $shape) {
            $keys = [];
            foreach ($shape->getKeyTypes() as $keyType) {
                foreach ($keyType->getConstantStrings() as $constant) {
                    $keys[] = $constant->getValue();
                }
            }
            $variants[] = \array_values(\array_unique($keys));
        }

        return [
            'line' => $node->getStartLine(),
            'variants' => ($variants === []) ? null : $variants,
        ];
    }
}
