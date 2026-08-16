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
        $this->analyse([__DIR__ . '/data/unescaped-func-call.php'], [
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 12],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 14],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 16],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 18],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 19],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 22],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 23],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 24],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 26],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 28],
            ['Output is not escaped, which risks XSS. Escape it with $this->h() or $this->escapeHtml() (or another escape method), or mark it as intentionally unescaped with $this->raw().', 29],
        ]);
    }
}
