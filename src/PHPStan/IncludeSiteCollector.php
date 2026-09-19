<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;

/**
 * A renderer helper that includes another template, the template it names and the variables it passes.
 *
 * Covers `partial()`, `layout()` and `beginElement()`, each of which resolves its name inside its own
 * directory (`partials/`, `layouts/`, `elements/`). `fetch()` is a slot lookup rather than an include, so it
 * is not collected. Types travel as descriptions, since collected data crosses files as JSON.
 *
 * `readable` says whether the variables the call passes could be read at all: a computed array has no
 * statically known keys, which the pairing rule reports rather than mistaking for a call that passes nothing.
 *
 * @implements Collector<MethodCall, array{line: int, template: string|null, readable: bool, provides: array<string, string>}>
 */
final class IncludeSiteCollector implements Collector
{
    /**
     * The helpers that include a template, with the directory the name resolves inside and the argument
     * position of the variables the call passes.
     */
    private const INCLUDES = [
        'partial' => ['directory' => 'partials', 'data' => 1],
        'layout' => ['directory' => 'layouts', 'data' => 1],
        'beginElement' => ['directory' => 'elements', 'data' => 1],
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return array{line: int, template: string|null, readable: bool, provides: array<string, string>}|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (! $node->name instanceof Identifier) {
            return null;
        }
        $include = self::INCLUDES[$node->name->toString()] ?? null;
        if ($include === null) {
            return null;
        }

        $arguments = $node->getArgs();
        $name = $arguments[0] ?? null;
        $names = ($name === null) ? [] : $scope->getType($name->value)->getConstantStrings();

        $provides = [];
        $readable = true;
        $data = $arguments[$include['data']] ?? null;
        if ($data !== null) {
            $shapes = $scope->getType($data->value)->getConstantArrays();
            $readable = ($shapes !== []);
            foreach ($shapes as $shape) {
                $keyTypes = $shape->getKeyTypes();
                $valueTypes = $shape->getValueTypes();
                foreach ($keyTypes as $index => $keyType) {
                    $keys = $keyType->getConstantStrings();
                    if ((\count($keys) === 1) && isset($valueTypes[$index])) {
                        $provides[$keys[0]->getValue()] = $valueTypes[$index]->describe(VerbosityLevel::precise());
                    }
                }
            }
        }

        return [
            'line' => $node->getStartLine(),
            'template' => ($names === []) ? null : "{$include['directory']}/{$names[0]->getValue()}",
            'readable' => $readable,
            'provides' => $provides,
        ];
    }
}
