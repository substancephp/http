<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\EmptyShare;
use SubstancePHP\HTTP\Exception\RenderingException\UnsupportedContentTypeException;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use SubstancePHP\HTTP\Renderer\JsonRenderer;
use SubstancePHP\HTTP\RendererFactory;
use SubstancePHP\HTTP\ShareInterface;
use SubstancePHP\HTTP\Templating;
use SubstancePHP\HTTP\RenderInput;
use TestUtil\TestUtil;

#[CoversClass(RendererFactory::class)]
#[CoversMethod(RendererFactory::class, '__construct')]
#[CoversMethod(RendererFactory::class, 'createRenderer')]
class RendererFactoryTest extends TestCase
{
    private function makeInstance(): RendererFactory
    {
        return new RendererFactory(new Templating(TestUtil::getFixtureRoot() . '/template', 'utf-8'));
    }

    #[Test]
    public function createRendererJson(): void
    {
        $input = new RenderInput('dummy', 'application/json', ['a' => 1]);
        $this->assertInstanceOf(JsonRenderer::class, $this->makeInstance()->createRenderer($input));
    }

    #[Test]
    public function createRendererHtml(): void
    {
        $input = new RenderInput('dummy', 'text/html; charset=utf-8', []);
        $this->assertInstanceOf(HtmlRenderer::class, $this->makeInstance()->createRenderer($input));
    }

    #[Test]
    public function createRendererResolvesSharedOnlyForHtml(): void
    {
        $factory = $this->makeInstance();
        $calls = 0;
        $share = function () use (&$calls): ShareInterface {
            $calls++;
            return new EmptyShare();
        };

        // Only an HTML renderer needs the shared variables, so a JSON response must not resolve them.
        $factory->createRenderer(new RenderInput('dummy', 'application/json', [], $share));
        $this->assertSame(0, $calls);

        $factory->createRenderer(new RenderInput('dummy', 'text/html; charset=utf-8', [], $share));
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function createRendererUnsupportedContentType(): void
    {
        $this->expectException(UnsupportedContentTypeException::class);
        $this->makeInstance()->createRenderer(new RenderInput('dummy', 'text/plain', 'body'));
    }
}
