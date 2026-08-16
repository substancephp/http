<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * Flags output of unescaped content via `echo` or `<?= ?>` in HTML templates.
 *
 * @implements Rule<Echo_>
 */
final class UnescapedOutputRule implements Rule
{
    #[\Override]
    /** @return class-string<Echo_> */
    public function getNodeType(): string
    {
        return Echo_::class;
    }

    #[\Override]
    /** @param Echo_ $node */
    public function processNode(Node $node, Scope $scope): array
    {
        return (new UnescapedOutputChecker())->checkExpressions($node->exprs, $scope);
    }
}
