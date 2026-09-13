<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\RenderingException\UnsupportedContentTypeException;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use SubstancePHP\HTTP\Renderer\JsonRenderer;
use SubstancePHP\HTTP\RendererFactory;
use TestUtil\TestUtil;

#[CoversClass(RendererFactory::class)]
#[CoversMethod(RendererFactory::class, '__construct')]
#[CoversMethod(RendererFactory::class, 'createRenderer')]
class RendererFactoryTest extends TestCase
{
    private function makeInstance(): RendererFactory
    {
        return new RendererFactory(TestUtil::getFixtureRoot() . '/template', 'utf-8');
    }

    #[Test]
    public function createRendererJson(): void
    {
        $renderer = $this->makeInstance()->createRenderer('dummy', 'application/json', ['a' => 1]);
        $this->assertInstanceOf(JsonRenderer::class, $renderer);
    }

    #[Test]
    public function createRendererHtml(): void
    {
        $renderer = $this->makeInstance()->createRenderer('dummy', 'text/html; charset=utf-8', []);
        $this->assertInstanceOf(HtmlRenderer::class, $renderer);
    }

    #[Test]
    public function createRendererUnsupportedContentType(): void
    {
        $this->expectException(UnsupportedContentTypeException::class);
        $this->makeInstance()->createRenderer('dummy', 'text/plain', 'body');
    }
}
