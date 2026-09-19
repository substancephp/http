<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\VerbosityLevel;

/**
 * The line span of every closure in a file.
 *
 * An action file returns one callback, and a helper closure declared inside it is indistinguishable from that
 * callback at a return statement: PHPStan gives a collector no access to the enclosing function, and an
 * anonymous function's scope reports no function name. The spans are what lets the pairing rule keep the
 * callback's own returns and drop the helpers' ones.
 *
 * Arrow functions need no entry: their body is an expression, so they contain no return statement to collect.
 *
 * @implements Collector<Closure, array{start: int, end: int}>
 */
final class ClosureSpanCollector implements Collector
{
    public function getNodeType(): string
    {
        return Closure::class;
    }

    /** @return array{start: int, end: int} */
    public function processNode(Node $node, Scope $scope): array
    {
        return ['start' => $node->getStartLine(), 'end' => $node->getEndLine()];
    }
}
