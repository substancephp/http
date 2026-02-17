<?php

declare(strict_types=1);

namespace Test\Renderer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Renderer\EmptyRenderer;

#[CoversClass(EmptyRenderer::class)]
#[CoversMethod(EmptyRenderer::class, 'render')]
class EmptyRendererTest extends TestCase
{
    #[Test]
    public function render(): void
    {
        $instance = new EmptyRenderer();
        $result = $instance->render();
        $this->assertSame('', $result);
    }
}
