<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\Type;

/**
 * One template's declared variables: the `@var` block at the top of the file, name to resolved type.
 *
 * The names come from the file itself, since a template has no signature to reflect, and each type comes
 * from the scope, where the declared tags have already been resolved. A template that declares nothing
 * contributes nothing, and one that does not declare `$this` is not a template at all (see
 * {@see UnescapedOutputChecker}).
 *
 * @implements Collector<Node\Stmt\InlineHTML, array<string, array{type: Type, line: int}>>
 */
final class TemplateVariablesCollector implements Collector
{
    /** One `@var <type> $<name>` tag, as documented for templates. */
    private const VARIABLE_TAG = '/@var\s+([^\s$*]+)\s+\$([A-Za-z_][A-Za-z0-9_]*)/';

    public function getNodeType(): string
    {
        return Node\Stmt\InlineHTML::class;
    }

    /** @return array<string, array{type: Type, line: int}>|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $source = @\file_get_contents($scope->getFile());
        if ($source === false) {
            return null;
        }

        // The declaration block is documented as sitting at the top of the file, before the first output.
        $head = \strstr($source, '?>', true);
        $declared = [];
        foreach (\explode("\n", ($head === false) ? $source : $head) as $index => $line) {
            if (\preg_match_all(self::VARIABLE_TAG, $line, $matches, \PREG_SET_ORDER) === 0) {
                continue;
            }
            foreach ($matches as $match) {
                if ($match[2] !== 'this') {
                    $declared[$match[2]] = ['type' => $scope->getVariableType($match[2]), 'line' => ($index + 1)];
                }
            }
        }
        return ($declared === []) ? null : $declared;
    }
}
