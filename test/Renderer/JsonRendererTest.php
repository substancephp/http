<?php

declare(strict_types=1);

namespace Test\Renderer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Renderer\JsonRenderer;

#[CoversClass(JsonRenderer::class)]
#[CoversMethod(JsonRenderer::class, 'render')]
class JsonRendererTest extends TestCase
{
    #[Test]
    #[TestWith([[], '[]'])]
    #[TestWith([new \stdClass(), '{}'])]
    #[TestWith([['items' => []],'{"items":[]}'])]
    #[TestWith([['items' => ['hello', 'world']],'{"items":["hello","world"]}'])]
    #[TestWith([['items' => 3],'{"items":3}'])]
    #[TestWith([90,'90'])]
    public function render(mixed $data, string $expectedOutput): void
    {
        $instance = new JsonRenderer($data);
        $result = $instance->render();
        $this->assertSame($expectedOutput, $result);
    }
}
