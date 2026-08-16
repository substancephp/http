<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * Flags output of unescaped content via `die()` or `exit()` in HTML templates.
 *
 * @implements Rule<Node\Expr\Exit_>
 */
final class UnescapedExitRule implements Rule
{
    #[\Override]
    /** @return class-string<Node\Expr\Exit_> */
    public function getNodeType(): string
    {
        return Node\Expr\Exit_::class;
    }

    #[\Override]
    /** @param Node\Expr\Exit_ $node */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->expr === null) {
            return [];
        }

        return (new UnescapedOutputChecker())->checkExpressions([$node->expr], $scope);
    }
}
