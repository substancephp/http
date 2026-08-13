<?php

declare(strict_types=1);

namespace Test\Renderer;

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
class HtmlRendererTest extends TestCase
{
    #[Test]
    #[TestWith([
        '/dummy-no-vars.html',
        ['hello' => 'world'],
        <<<HTML
        <html lang="en">
        <head><title>Hi</title></head>
        <body>hello</body>
        </html>
        HTML,
    ])]
    #[TestWith([
        '/dummy-vars.php',
        [
            'wordA' => 'world',
            'count' => 9,
            'wordB' => '<b>bolded</b>',
            'wordC' => 'hi',
        ],
        <<<HTML

        <html lang="en">
            <head>
            <meta charset="utf8">
            <title>Hi</title>
            </head>
            <body>
                <p>Hello, world</p>
                <p>Count is 9</p>
                <p>Unescaped A: <b>bolded</b></p>
                <p>Escaped A: &lt;b&gt;bolded&lt;/b&gt;</p>
                <p>Escaped B: hi</p>
            </body>
        </html>
        HTML,
    ])]
    public function renderHappyPath(string $template, mixed $data, string $expectedOutput): void
    {
        $templatePath = TestUtil::getFixtureRoot() . '/template' . $template;
        $renderer = new HtmlRenderer($templatePath, $data);
        $this->assertSame(\trim($expectedOutput), \trim($renderer->render()));
    }

    #[Test]
    public function renderInvalidUnicode(): void
    {
        $template = '/dummy-vars.php';
        $data = [
            'wordA' => 'world',
            'count' => 9,
            'wordB' => '<b>bolded</b>',
            'wordC' => \chr(0xff),
        ];
        $templatePath = TestUtil::getFixtureRoot() . '/template' . $template;
        $expectedOutput = <<<HTML
        <html lang="en">
            <head>
            <meta charset="utf8">
            <title>Hi</title>
            </head>
            <body>
                <p>Hello, world</p>
                <p>Count is 9</p>
                <p>Unescaped A: <b>bolded</b></p>
                <p>Escaped A: &lt;b&gt;bolded&lt;/b&gt;</p>
                <p>Escaped B: �</p>
            </body>
        </html>
        HTML;
        $renderer = new HtmlRenderer($templatePath, $data);
        $this->assertSame(\trim($expectedOutput), \trim($renderer->render()));
    }
}
