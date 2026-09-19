<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;

/**
 * The variables a single `return` statement in an action provides, with their types, one entry per possible
 * shape.
 *
 * Types travel as descriptions because collected data crosses files as JSON, so the pairing rule resolves
 * them again before comparing. A return whose type carries no known key names at all (an untyped `array`,
 * `mixed`) reports `null` variants, which the rule treats as unverifiable rather than as providing nothing.
 *
 * The file-level return, which yields the callback rather than data, reports the callback's line span
 * instead, along with no variants.
 *
 * @implements Collector<Node\Stmt\Return_, array{line: int, variants: array<int, array<string, string>>|null, callback?: array{start: int, end: int}}>
 */
final class ActionDataCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Stmt\Return_::class;
    }

    /** @return array{line: int, variants: array<int, array<string, string>>|null, callback?: array{start: int, end: int}} */
    public function processNode(Node $node, Scope $scope): array
    {
        // Action files return their callback at the top level, so this returns the callback's span rather than
        // any data, which is how the rule tells its own returns from those of helper closures inside it.
        if (! $scope->isInAnonymousFunction()) {
            return ($node->expr instanceof Closure)
                ? [
                    'line' => $node->getStartLine(),
                    'variants' => [],
                    'callback' => ['start' => $node->expr->getStartLine(), 'end' => $node->expr->getEndLine()],
                ]
                : ['line' => $node->getStartLine(), 'variants' => []];
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

        if ($variants === []) {
            // A shape whose values are not constant, which is what a variable holds once assigned or what a
            // helper documents, keeps its keys as a union of constants while saying nothing about the values.
            // The names are then usable and each is reported as `mixed`, so the check stays quiet about types.
            foreach ($type->getArrays() as $array) {
                $variant = [];
                foreach ($array->getKeyType()->getConstantStrings() as $key) {
                    $variant[$key->getValue()] = 'mixed';
                }
                if ($variant !== []) {
                    $variants[] = $variant;
                }
            }
        }

        return [
            'line' => $node->getStartLine(),
            'variants' => ($variants === []) ? null : $variants,
        ];
    }
}
