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
            'word' => 'world',
            'count' => 9,
            'tag' => '<b>bolded</b>',
        ],
        <<<HTML
        
        <html lang="en">
        <head><title>Hi</title></head>
        <body>
            <p>Hello, world</p>
            <p>Count is 9</p>
            <p>Unescaped: <b>bolded</b></p>
            <p>Escaped: &lt;b&gt;bolded&lt;/b&gt;</p>
        </body>
        </html>
        HTML,
    ])]
    public function render(string $template, mixed $data, string $expectedOutput): void
    {
        $templatePath = TestUtil::getFixtureRoot() . '/template' . $template;
        $renderer = new HtmlRenderer($templatePath, $data);
        $this->assertSame($expectedOutput, $renderer->render());
    }
}
