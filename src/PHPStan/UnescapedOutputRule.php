<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * Flags output of unescaped content via `echo` or `<?= ?>` in HTML templates.
 *
 * @implements Rule<Node\Stmt\Echo_>
 */
final class UnescapedOutputRule implements Rule
{
    #[\Override]
    /** @return class-string<Node\Stmt\Echo_> */
    public function getNodeType(): string
    {
        return Node\Stmt\Echo_::class;
    }

    #[\Override]
    /** @param Node\Stmt\Echo_ $node */
    public function processNode(Node $node, Scope $scope): array
    {
        return (new UnescapedOutputChecker())->checkExpressions($node->exprs, $scope);
    }
}
