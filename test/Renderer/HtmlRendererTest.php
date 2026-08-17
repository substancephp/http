<?php

declare(strict_types=1);

namespace Test\Renderer;

use Laminas\Escaper\Escaper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use TestUtil\TestUtil;

#[CoversClass(HtmlRenderer::class)]
#[CoversMethod(HtmlRenderer::class, 'render')]
#[CoversMethod(HtmlRenderer::class, 'e')]
#[CoversMethod(HtmlRenderer::class, 'raw')]
#[CoversMethod(HtmlRenderer::class, 'h')]
#[CoversMethod(HtmlRenderer::class, 'escapeHtml')]
#[CoversMethod(HtmlRenderer::class, 'escapeHtmlAttr')]
#[CoversMethod(HtmlRenderer::class, 'escapeJs')]
#[CoversMethod(HtmlRenderer::class, 'escapeCss')]
#[CoversMethod(HtmlRenderer::class, 'escapeUrl')]
#[CoversMethod(HtmlRenderer::class, 'a')]
#[CoversMethod(HtmlRenderer::class, 'j')]
#[CoversMethod(HtmlRenderer::class, 'c')]
#[CoversMethod(HtmlRenderer::class, 'u')]
class HtmlRendererTest extends TestCase
{
    #[Test]
    public function render(): void
    {
        $data = [
            'word' => 'world',
            'unsafeWord' => '<b>bolded</b>',
        ];
        $expectedOutput = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <body>
            <p>Hello, world</p>
            <p>Unescaped: <b>bolded</b></p>
            <p>Escaped: &lt;b&gt;bolded&lt;/b&gt;</p>
        </body>
        </html>
        HTML;

        $this->assertSame(\trim($expectedOutput), \trim($this->makeRenderer($data)->render()));
    }

    #[Test]
    public function renderSubstitutesInvalidUtf8(): void
    {
        $data = [
            'word' => 'world',
            'unsafeWord' => "\xff",
        ];
        $expectedOutput = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <body>
            <p>Hello, world</p>
            <p>Unescaped: \xff</p>
            <p>Escaped: \u{FFFD}</p>
        </body>
        </html>
        HTML;

        $this->assertSame(\trim($expectedOutput), \trim($this->makeRenderer($data)->render()));
    }

    #[Test]
    public function renderThrowsOnNonVariableDataKey(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid template data');
        $this->makeRenderer(['1' => 'not a variable name'])->render();
    }

    #[Test]
    #[TestWith(['<b>x</b>', '&lt;b&gt;x&lt;/b&gt;'])]
    #[TestWith(["a'b\"c", 'a&#039;b&quot;c'])]
    #[TestWith(["\xff", "\u{FFFD}"])]
    #[TestWith([42, '42'])]
    public function e(mixed $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->e($content));
    }

    #[Test]
    #[TestWith(['<b>x</b>', '<b>x</b>'])]
    #[TestWith([null, ''])]
    #[TestWith([42, '42'])]
    public function raw(mixed $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->raw($content));
    }

    #[Test]
    #[TestWith(['<b>bold</b>', '&lt;b&gt;bold&lt;/b&gt;'])]
    #[TestWith(['a & b', 'a &amp; b'])]
    public function h(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->h($content));
    }

    #[Test]
    #[TestWith(['<script>alert("x")</script>', '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;'])]
    #[TestWith(["it's \"quoted\"", 'it&#039;s &quot;quoted&quot;'])]
    #[TestWith(["\xff", "\u{FFFD}"])]
    public function escapeHtml(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->escapeHtml($content));
    }

    #[Test]
    #[TestWith([
        '"><script>alert(1)</script>',
        '&quot;&gt;&lt;script&gt;alert&#x28;1&#x29;&lt;&#x2F;script&gt;',
    ])]
    #[TestWith(["it's \"quoted\"", 'it&#x27;s&#x20;&quot;quoted&quot;'])]
    public function escapeHtmlAttr(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->escapeHtmlAttr($content));
    }

    #[Test]
    #[TestWith([
        '</script><script>alert(1)</script>',
        '\x3C\x2Fscript\x3E\x3Cscript\x3Ealert\x281\x29\x3C\x2Fscript\x3E',
    ])]
    #[TestWith(["line1\nline2\r", 'line1\x0Aline2\x0D'])]
    public function escapeJs(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->escapeJs($content));
    }

    #[Test]
    #[TestWith([
        '</style><script>alert(1)</script>',
        '\3C \2F style\3E \3C script\3E alert\28 1\29 \3C \2F script\3E ',
    ])]
    #[TestWith(["'\"<>&", '\27 \22 \3C \3E \26 '])]
    public function escapeCss(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->escapeCss($content));
    }

    #[Test]
    #[TestWith(["http://example.com/?q=a b&x='y'", 'http%3A%2F%2Fexample.com%2F%3Fq%3Da%20b%26x%3D%27y%27'])]
    #[TestWith(['a<b>c', 'a%3Cb%3Ec'])]
    public function escapeUrl(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->escapeUrl($content));
    }

    #[Test]
    #[TestWith([
        '"><script>alert(1)</script>',
        '&quot;&gt;&lt;script&gt;alert&#x28;1&#x29;&lt;&#x2F;script&gt;',
    ])]
    #[TestWith(["it's \"quoted\"", 'it&#x27;s&#x20;&quot;quoted&quot;'])]
    public function a(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->a($content));
    }

    #[Test]
    #[TestWith([
        '</script><script>alert(1)</script>',
        '\x3C\x2Fscript\x3E\x3Cscript\x3Ealert\x281\x29\x3C\x2Fscript\x3E',
    ])]
    #[TestWith(["line1\nline2\r", 'line1\x0Aline2\x0D'])]
    public function j(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->j($content));
    }

    #[Test]
    #[TestWith([
        '</style><script>alert(1)</script>',
        '\3C \2F style\3E \3C script\3E alert\28 1\29 \3C \2F script\3E ',
    ])]
    #[TestWith(["'\"<>&", '\27 \22 \3C \3E \26 '])]
    public function c(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->c($content));
    }

    #[Test]
    #[TestWith(["http://example.com/?q=a b&x='y'", 'http%3A%2F%2Fexample.com%2F%3Fq%3Da%20b%26x%3D%27y%27'])]
    #[TestWith(['a<b>c', 'a%3Cb%3Ec'])]
    public function u(string $content, string $expected): void
    {
        $this->assertSame($expected, $this->makeRenderer()->u($content));
    }

    /** @param array<array-key, mixed> $data */
    private function makeRenderer(array $data = []): HtmlRenderer
    {
        return new HtmlRenderer(
            TestUtil::getFixtureRoot() . '/template/dummy-vars.php',
            $data,
            new Escaper('utf-8'),
        );
    }
}
