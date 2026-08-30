<?php

declare(strict_types=1);

namespace Test\Renderer;

use Laminas\Escaper\Escaper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\RenderingException\MissingElementException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingLayoutException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingPartialException;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use TestUtil\TestUtil;

#[CoversClass(HtmlRenderer::class)]
#[CoversMethod(HtmlRenderer::class, 'render')]
#[CoversMethod(HtmlRenderer::class, 'layout')]
#[CoversMethod(HtmlRenderer::class, 'content')]
#[CoversMethod(HtmlRenderer::class, 'start')]
#[CoversMethod(HtmlRenderer::class, 'append')]
#[CoversMethod(HtmlRenderer::class, 'prepend')]
#[CoversMethod(HtmlRenderer::class, 'stop')]
#[CoversMethod(HtmlRenderer::class, 'fetch')]
#[CoversMethod(HtmlRenderer::class, 'beginElement')]
#[CoversMethod(HtmlRenderer::class, 'endElement')]
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
#[CoversMethod(HtmlRenderer::class, 'partial')]
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
        <div id="layout"><!DOCTYPE html>
        <html lang="en">
        <body>
            <p>Hello, world</p>
            <p>Unescaped: <b>bolded</b></p>
            <p>Escaped: &lt;b&gt;bolded&lt;/b&gt;</p>
        </body>
        </html>
        </div>
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
        <div id="layout"><!DOCTYPE html>
        <html lang="en">
        <body>
            <p>Hello, world</p>
            <p>Unescaped: \xff</p>
            <p>Escaped: \u{FFFD}</p>
        </body>
        </html>
        </div>
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

    #[Test]
    public function partial(): void
    {
        $out = $this->makeRenderer()->partial('share', [
            'url' => 'https://example.com/?q=a b',
            'label' => '<b>Go</b>',
        ]);
        $this->assertSame(
            '<a href="https%3A%2F%2Fexample.com%2F%3Fq%3Da%20b">&lt;b&gt;Go&lt;/b&gt;</a>',
            $out,
        );
    }

    #[Test]
    public function partialDoesNotLeakViewData(): void
    {
        $renderer = $this->makeRenderer(['word' => 'view-word']);
        $this->assertSame('CLEAN', $renderer->partial('leak-probe', []));
    }

    #[Test]
    public function partialSupportsNesting(): void
    {
        $this->assertSame('[(hi)]', $this->makeRenderer()->partial('outer', ['word' => 'hi']));
    }

    #[Test]
    public function partialThrowsWhenTemplateMissing(): void
    {
        $this->expectException(MissingPartialException::class);
        $this->expectExceptionMessage('nonexistent');
        $this->makeRenderer()->partial('nonexistent');
    }

    #[Test]
    public function partialRethrowsErrorsFromThePartialItself(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('boom');
        $this->makeRenderer()->partial('error-bomb');
    }

    #[Test]
    public function partialDataKeysCannotCollideWithEngineState(): void
    {
        $out = $this->makeRenderer()->partial('collision-probe', [
            'path' => 'user-path',
            'data' => 'user-data',
            'started' => 'user-started',
            'result' => 'user-result',
        ]);
        $this->assertSame('user-path|user-data|user-started|user-result', $out);
    }

    #[Test]
    public function renderDataKeysCannotCollideWithEngineState(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/collision-view.html.php',
            data: ['started' => 'user-started', 'result' => 'user-result'],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->assertSame(
            '<div id="layout">user-started|user-result</div>',
            \trim($renderer->render()),
        );
    }

    #[Test]
    public function renderAppliesDeclaredLayout(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/declared-layout-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('<title>My Site</title>', $out);
        $this->assertStringContainsString('<p>view body</p>', $out);
        $this->assertStringNotContainsString('<div id="layout">', $out);
    }

    #[Test]
    public function renderStacksLayouts(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/stacked-layout-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('(outer:', $out);
        $this->assertStringContainsString('[inner:', $out);
        $this->assertStringContainsString('<p>view body</p>', $out);
    }

    #[Test]
    public function renderThrowsWhenDeclaredLayoutMissing(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/missing-layout-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->expectException(MissingLayoutException::class);
        $this->expectExceptionMessage('nonexistent');
        $renderer->render();
    }

    #[Test]
    public function renderThrowsWhenDefaultLayoutMissing(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/dummy-vars.html.php',
            data: ['word' => 'world', 'unsafeWord' => 'x'],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
            defaultLayout: 'does-not-exist',
        );
        $this->expectException(MissingLayoutException::class);
        $this->expectExceptionMessage('does-not-exist');
        $renderer->render();
    }

    #[Test]
    public function renderThrowsOnCircularLayoutDeclaration(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/cyclic-layout-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Circular layout declaration: cyclic');
        $renderer->render();
    }

    #[Test]
    public function partialCannotChangeTheEnclosingLayout(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/meddling-partial-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('<div id="layout">', $out);
        $this->assertStringContainsString('<p>meddled</p>', $out);
    }

    #[Test]
    public function contentReturnsEmptyStringFromAView(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/content-in-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->assertStringContainsString('[]', $renderer->render());
    }

    #[Test]
    public function slotsFlowThroughToTheLayout(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/slot-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('[<p>sidebar content</p>]', $out);
        $this->assertStringContainsString(
            '<scripts><script>0</script><script>a</script><script>b</script></scripts>',
            $out,
        );
        $this->assertStringContainsString('<empty></empty>', $out);
        $this->assertStringContainsString('<replace><p>second</p></replace>', $out);
        $this->assertStringContainsString('<p>body</p>', $out);
    }

    #[Test]
    public function slotsFetchFallsBackWhenNeverFilled(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/no-slot-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('[fallback]', $out);
        $this->assertStringContainsString('<scripts></scripts>', $out);
        $this->assertStringContainsString('<empty>FALLBACK</empty>', $out);
    }

    #[Test]
    public function stopWithoutCaptureThrows(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/stray-stop-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('stop() called with no open capture');
        $renderer->render();
    }

    #[Test]
    public function elementRendersBodyAndParams(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/element-card-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('<div class="card"><h2>Hi</h2>', $out);
        $this->assertStringContainsString('<p>card body</p>', $out);
    }

    #[Test]
    public function elementSubSlotsAreScopedToTheirInstance(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/two-panels-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        // each element renders only its own sub-slot, and the page slot holds only the page footer
        $this->assertSame(1, \substr_count($out, '[<p>footer A</p>]'));
        $this->assertSame(1, \substr_count($out, '[<p>footer B</p>]'));
        $this->assertSame(1, \substr_count($out, '[page:<p>page footer</p>]'));
    }

    #[Test]
    public function elementsNest(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/nested-elements-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $out = $renderer->render();
        $this->assertStringContainsString('<div class="card"><h2>Nested</h2>', $out);
        $this->assertStringContainsString('<div class="panel"><p>inner</p>[none]</div>', $out);
    }

    #[Test]
    public function elementThrowsWhenTemplateMissing(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/missing-element-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->expectException(MissingElementException::class);
        $this->expectExceptionMessage('nonexistent');
        $renderer->render();
    }

    #[Test]
    public function elementRethrowsErrorsFromTheElementItself(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/element-error-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('boom');
        $renderer->render();
    }

    #[Test]
    public function endElementWithoutBeginThrows(): void
    {
        $renderer = new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/stray-end-element-view.html.php',
            data: [],
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('endElement() called with no open element');
        $renderer->render();
    }

    /** @param array<array-key, mixed> $data */
    private function makeRenderer(array $data = []): HtmlRenderer
    {
        return new HtmlRenderer(
            templatePath: TestUtil::getFixtureRoot() . '/template/dummy-vars.html.php',
            data: $data,
            escaper: new Escaper('utf-8'),
            templateRoot: TestUtil::getFixtureRoot() . '/template',
        );
    }
}
