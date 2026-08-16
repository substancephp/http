<?php

declare(strict_types=1);

namespace Test\PHPStan;

use PHPUnit\Framework\Attributes\Test;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use SubstancePHP\HTTP\PHPStan\UnescapedExitRule;

/** @extends RuleTestCase<UnescapedExitRule> */
final class UnescapedExitRuleTest extends RuleTestCase
{
    #[\Override]
    protected function getRule(): UnescapedExitRule
    {
        return new UnescapedExitRule();
    }

    #[Test]
    public function flagsUnescapedExitOutputInHtmlTemplates(): void
    {
        $this->analyse([__DIR__ . '/data/unescaped-exit.php'], [
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 16],
        ]);
    }
}
