<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;
use SubstancePHP\HTTP\ShareInterface;

/**
 * A variable an application's share publishes through a promoted constructor parameter, with its type.
 *
 * A share usually publishes its variables this way, as `GreetingShare` does, so this is the common half of
 * the pair that {@see SharePropertyCollector} belongs to. The result is a list, like its sibling's, so both
 * collectors carry the same shape.
 *
 * @implements Collector<Node\Param, list<array{variable: string, type: string}>>
 */
final class ShareParameterCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Param::class;
    }

    /** @return list<array{variable: string, type: string}> */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();
        $name = $node->var;
        if (! $node->isPublic() || $class === null || ! $name instanceof Variable || ! \is_string($name->name)) {
            return [];
        }
        if (! $class->implementsInterface(ShareInterface::class)) {
            return [];
        }
        if (\str_starts_with($class->getName(), 'SubstancePHP\\HTTP\\')) {
            return [];
        }

        return [[
            'variable' => $name->name,
            'type' => $class->getProperty($name->name, $scope)->getReadableType()->describe(VerbosityLevel::precise()),
        ]];
    }
}
