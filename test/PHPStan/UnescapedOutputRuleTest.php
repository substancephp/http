<?php

declare(strict_types=1);

namespace Test\PHPStan;

use PHPUnit\Framework\Attributes\Test;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use SubstancePHP\HTTP\PHPStan\UnescapedOutputRule;

/** @extends RuleTestCase<UnescapedOutputRule> */
final class UnescapedOutputRuleTest extends RuleTestCase
{
    #[\Override]
    protected function getRule(): UnescapedOutputRule
    {
        return new UnescapedOutputRule();
    }

    #[Test]
    public function flagsUnescapedOutputInHtmlTemplates(): void
    {
        $expectedMessage = 'Output is not escaped, which risks XSS. Escape it with $this->h() or '
            . '$this->escapeHtml() (or another escape method), or mark it as intentionally '
            . 'unescaped with $this->raw().';

        $this->analyse([__DIR__ . '/data/unescaped-output.php'], [
            [$expectedMessage, 18],
            [$expectedMessage, 20],
            [$expectedMessage, 23],
            [$expectedMessage, 27],
            [$expectedMessage, 28],
        ]);
    }

    #[Test]
    public function doesNotFlagOutsideHtmlTemplates(): void
    {
        $this->analyse([__DIR__ . '/data/not-a-template.php'], []);
    }
}
