<?php

declare(strict_types=1);

namespace Test\PHPStan;

use PHPUnit\Framework\Attributes\Test;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use SubstancePHP\HTTP\PHPStan\UnescapedOutputFuncCallRule;

/** @extends RuleTestCase<UnescapedOutputFuncCallRule> */
final class UnescapedOutputFuncCallRuleTest extends RuleTestCase
{
    #[\Override]
    protected function getRule(): UnescapedOutputFuncCallRule
    {
        return new UnescapedOutputFuncCallRule();
    }

    #[Test]
    public function flagsUnescapedOutputFunctionCallsInHtmlTemplates(): void
    {
        $expectedMessage = 'Output is not escaped, which risks XSS. Escape it with $this->h() or '
            . '$this->escapeHtml() (or another escape method), or mark it as intentionally '
            . 'unescaped with $this->raw().';

        $this->analyse([__DIR__ . '/data/unescaped-func-call.php'], [
            [$expectedMessage, 12],
            [$expectedMessage, 14],
            [$expectedMessage, 16],
            [$expectedMessage, 18],
            [$expectedMessage, 19],
            [$expectedMessage, 22],
            [$expectedMessage, 23],
            [$expectedMessage, 24],
            [$expectedMessage, 26],
            [$expectedMessage, 28],
            [$expectedMessage, 29],
        ]);
    }
}
