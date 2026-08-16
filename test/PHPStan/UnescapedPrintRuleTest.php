<?php

declare(strict_types=1);

namespace Test\PHPStan;

use PHPUnit\Framework\Attributes\Test;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use SubstancePHP\HTTP\PHPStan\UnescapedPrintRule;

/** @extends RuleTestCase<UnescapedPrintRule> */
final class UnescapedPrintRuleTest extends RuleTestCase
{
    #[\Override]
    protected function getRule(): UnescapedPrintRule
    {
        return new UnescapedPrintRule();
    }

    #[Test]
    public function flagsUnescapedPrintOutputInHtmlTemplates(): void
    {
        $expectedMessage = 'Output is not escaped, which risks XSS. Escape it with $this->h() or '
            . '$this->escapeHtml() (or another escape method), or mark it as intentionally '
            . 'unescaped with $this->raw().';

        $this->analyse([__DIR__ . '/data/unescaped-print.php'], [
            [$expectedMessage, 10],
        ]);
    }
}
