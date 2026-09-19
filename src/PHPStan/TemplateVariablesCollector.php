<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;

/**
 * One template's declared variables: the `@var` block at the top of the file, name to type description and
 * line.
 *
 * The collector reports from every statement rather than from inline HTML, because PHPStan drops an inline
 * HTML chunk that holds nothing but whitespace, which would leave a template whose body is pure PHP with no
 * node to report from at all. It reads each file once, remembers whether the file is a template, and says
 * nothing for files that are not.
 *
 * Types travel as descriptions, since collected data crosses files as JSON, and the last statement of a file
 * reports the final view of them: statements before the declaration block still see the declared names as
 * undefined, and the pairing rule keeps the last entry per name.
 *
 * @implements Collector<Node\Stmt, array<string, array{type: string, line: int}>>
 */
final class TemplateVariablesCollector implements Collector
{
    /** One `@var <type> $<name>` tag; the type may itself contain spaces, as `array<int, string>` does. */
    private const VARIABLE_TAG = '/@var\s+[^$]*\$([A-Za-z_][A-Za-z0-9_]*)/';

    /** A template is a file declaring `$this` as the renderer, the marker the escaping rules use too. */
    private const RENDERER_TAG = '/@var\s+[^$]*HtmlRenderer[^$]*\$this/';

    /**
     * The declared names per file, with the line each is on, memoized: this runs once per statement.
     *
     * @var array<string, array<string, int>>
     */
    private static array $declared = [];

    public function getNodeType(): string
    {
        return Node\Stmt::class;
    }

    /** @return array<string, array{type: string, line: int}> */
    public function processNode(Node $node, Scope $scope): array
    {
        $file = $scope->getFile();
        $declared = self::$declared[$file] ??= self::declaredVariables($file);

        $variables = [];
        foreach ($declared as $name => $line) {
            $variables[$name] = [
                'type' => $scope->getVariableType($name)->describe(VerbosityLevel::precise()),
                'line' => $line,
            ];
        }
        return $variables;
    }

    /**
     * The names a template declares, with the line each is declared on, or an empty array when the file is not
     * a template.
     *
     * @return array<string, int>
     */
    private static function declaredVariables(string $file): array
    {
        $source = @\file_get_contents($file);
        if ($source === false) {
            return [];
        }

        // The declaration block is documented as sitting at the top of the file, before the first output.
        $head = \strstr($source, '?>', true);
        $head = ($head === false) ? $source : $head;
        if (\preg_match(self::RENDERER_TAG, $head) !== 1) {
            return [];
        }

        $declared = [];
        foreach (\explode("\n", $head) as $index => $line) {
            if (\preg_match_all(self::VARIABLE_TAG, $line, $matches, \PREG_SET_ORDER) === 0) {
                continue;
            }
            foreach ($matches as $match) {
                if ($match[1] !== 'this') {
                    $declared[$match[1]] = ($index + 1);
                }
            }
        }
        return $declared;
    }
}
