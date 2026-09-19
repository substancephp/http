<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;

/**
 * The variables a single `return` statement in an action provides, with their types, one entry per possible
 * shape.
 *
 * Types travel as descriptions because collected data crosses files as JSON, so the pairing rule resolves
 * them again before comparing. A return whose type has no statically known keys (an untyped `array`, `mixed`)
 * reports `null` variants, which the rule treats as unverifiable rather than as providing nothing.
 *
 * @implements Collector<Node\Stmt\Return_, array{line: int, variants: array<int, array<string, string>>|null}>
 */
final class ActionDataCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Stmt\Return_::class;
    }

    /** @return array{line: int, variants: array<int, array<string, string>>|null}|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        // Action files return their callback at the top level; only returns inside it are response data.
        if (! $scope->isInAnonymousFunction()) {
            return null;
        }

        // A bare `return`, or one returning null, is a bodyless response: that branch renders no template, so
        // it contributes no variant at all, neither satisfying nor violating the template's contract.
        if ($node->expr === null) {
            return ['line' => $node->getStartLine(), 'variants' => []];
        }

        $type = $scope->getType($node->expr);
        if ($type->isNull()->yes()) {
            return ['line' => $node->getStartLine(), 'variants' => []];
        }

        $variants = [];
        foreach ($type->getConstantArrays() as $shape) {
            $keyTypes = $shape->getKeyTypes();
            $valueTypes = $shape->getValueTypes();
            $variant = [];
            foreach ($keyTypes as $index => $keyType) {
                $keys = $keyType->getConstantStrings();
                if ((\count($keys) === 1) && isset($valueTypes[$index])) {
                    $variant[$keys[0]->getValue()] = $valueTypes[$index]->describe(VerbosityLevel::precise());
                }
            }
            $variants[] = $variant;
        }

        return [
            'line' => $node->getStartLine(),
            'variants' => ($variants === []) ? null : $variants,
        ];
    }
}
