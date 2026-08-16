<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * Flags output of unescaped content via `print` in HTML templates.
 *
 * @implements Rule<Node\Expr\Print_>
 */
final class UnescapedPrintRule implements Rule
{
    #[\Override]
    /** @return class-string<Node\Expr\Print_> */
    public function getNodeType(): string
    {
        return Node\Expr\Print_::class;
    }

    #[\Override]
    /** @param Node\Expr\Print_ $node */
    public function processNode(Node $node, Scope $scope): array
    {
        return (new UnescapedOutputChecker())->checkExpressions([$node->expr], $scope);
    }
}
