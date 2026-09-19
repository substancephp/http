<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;
use SubstancePHP\HTTP\ShareInterface;

/**
 * A variable an application's share publishes through a public property, with the type it declares.
 *
 * The type comes from the scope, so a `@var` tag on the property counts, and travels as a description
 * because collected data crosses files as JSON. Only an application's own {@see ShareInterface}
 * implementations report; the framework's default is not an application's share.
 *
 * @implements Collector<Property, list<array{variable: string, type: string}>>
 */
final class SharePropertyCollector implements Collector
{
    public function getNodeType(): string
    {
        return Property::class;
    }

    /** @return list<array{variable: string, type: string}> */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();
        if (! $node->isPublic() || $node->isStatic() || $class === null) {
            return [];
        }
        if (! $class->implementsInterface(ShareInterface::class)) {
            return [];
        }
        if (\str_starts_with($class->getName(), 'SubstancePHP\\HTTP\\')) {
            return [];
        }

        $variables = [];
        foreach ($node->props as $item) {
            $name = $item->name->toString();
            $variables[] = [
                'variable' => $name,
                'type' => $class->getProperty($name, $scope)->getReadableType()->describe(VerbosityLevel::precise()),
            ];
        }
        return $variables;
    }
}
