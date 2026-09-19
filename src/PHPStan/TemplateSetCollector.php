<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use SubstancePHP\HTTP\AltTemplates;
use SubstancePHP\HTTP\DefaultTemplate;

/**
 * The template set an action declares, read from {@see DefaultTemplate} and {@see AltTemplates} on the
 * callback its file returns.
 *
 * A declaration whose arguments are not literals or constants reports no names, and `unreadable`, so the
 * pairing rule can report it instead of quietly checking nothing.
 *
 * @implements Collector<Closure, array{line: int, default: string|null, alternatives: string[], unreadable: bool}>
 */
final class TemplateSetCollector implements Collector
{
    public function getNodeType(): string
    {
        return Closure::class;
    }

    /** @return array{line: int, default: string|null, alternatives: string[], unreadable: bool}|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $default = null;
        $alternatives = [];
        $unreadable = false;
        $declared = false;

        foreach ($node->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                $name = $scope->resolveName($attribute->name);
                if ($name === DefaultTemplate::class) {
                    $declared = true;
                    $literals = self::literals($attribute->args[0]->value ?? null, $scope);
                    $default = $literals[0] ?? null;
                    $unreadable = $unreadable || ($literals === []);
                }
                if ($name === AltTemplates::class) {
                    $declared = true;
                    $literals = self::literals($attribute->args[0]->value ?? null, $scope);
                    $alternatives = [...$alternatives, ...$literals];
                    $unreadable = $unreadable || ($literals === []);
                }
            }
        }

        if (! $declared) {
            return null;
        }

        return [
            'line' => $node->getStartLine(),
            'default' => $default,
            'alternatives' => \array_values(\array_unique($alternatives)),
            'unreadable' => $unreadable,
        ];
    }

    /**
     * The literal strings an attribute argument names, whether it is a string or a list of them.
     *
     * @return string[]
     */
    private static function literals(?Expr $argument, Scope $scope): array
    {
        if ($argument === null) {
            return [];
        }

        $type = $scope->getType($argument);
        $literals = [];
        foreach ($type->getConstantStrings() as $literal) {
            $literals[] = $literal->getValue();
        }
        foreach ($type->getConstantArrays() as $array) {
            foreach ($array->getValueTypes() as $value) {
                foreach ($value->getConstantStrings() as $literal) {
                    $literals[] = $literal->getValue();
                }
            }
        }

        return \array_values(\array_unique($literals));
    }
}
