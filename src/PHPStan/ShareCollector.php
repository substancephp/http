<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use SubstancePHP\HTTP\ShareInterface;

/**
 * Every class declaring itself a {@see ShareInterface}, with the variable names it publishes.
 *
 * Those variables are the public, non-static properties, including public promoted constructor parameters,
 * since they are what the renderer spreads into template variables.
 *
 * @implements Collector<Class_, array{class: string, variables: string[]}>
 */
final class ShareCollector implements Collector
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    /** @return array{class: string, variables: string[]}|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        foreach ($node->implements as $implemented) {
            if ($scope->resolveName($implemented) !== ShareInterface::class) {
                continue;
            }
            $class = $node->namespacedName?->toString();
            if ($class === null) {
                return null;
            }

            $variables = [];
            foreach ($node->getProperties() as $property) {
                if (! $property->isPublic() || $property->isStatic()) {
                    continue;
                }
                foreach ($property->props as $item) {
                    $variables[] = $item->name->toString();
                }
            }
            foreach ($node->getMethod('__construct')?->getParams() ?? [] as $parameter) {
                $name = $parameter->var;
                if ($parameter->isPublic() && $name instanceof Variable && \is_string($name->name)) {
                    $variables[] = $name->name;
                }
            }

            return ['class' => $class, 'variables' => \array_values(\array_unique($variables))];
        }

        return null;
    }
}
