<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Application;
use SubstancePHP\HTTP\SubstanceProvider;
use TestUtil\Fixture\ApplicationProvider;
use TestUtil\Fixture\Middleware\AttributeGatheringMiddleware;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;

#[CoversClass(Application::class)]
#[CoversMethod(Application::class, '__construct')]
#[CoversMethod(Application::class, 'execute')]
#[CoversMethod(Application::class, 'get')]
#[CoversMethod(Application::class, 'has')]
class ApplicationTest extends TestCase
{
    #[Test]
    public function happyPath(): void
    {
        $env = ['FOO' => 'fooval', 'BAR' => 'barval'];
        $actionRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'action']);
        $instance = new Application(
            env: $env,
            actionRoot: $actionRoot,
            providers: [
                SubstanceProvider::class,
                ApplicationProvider::class,
            ],
            middlewares: [
                ExampleMiddlewareA::class,
                ExampleMiddlewareB::class,
                ExampleMiddlewareC::class,
                AttributeGatheringMiddleware::class,
            ],
        );
        $this->assertTrue($instance->has(ExampleMiddlewareA::class));
        $this->assertTrue($instance->has('foob.ar'));
        $this->assertFalse($instance->has('cool'));
        $this->assertInstanceOf(ExampleMiddlewareA::class, $instance->get(ExampleMiddlewareA::class));

        $attributeGatheringMiddleware = $instance->get(AttributeGatheringMiddleware::class);
        $this->assertInstanceOf(AttributeGatheringMiddleware::class, $attributeGatheringMiddleware);
        $this->assertFalse($attributeGatheringMiddleware->called);

        $instance->execute();

        $this->assertTrue($attributeGatheringMiddleware->called);
    }

    #[Test]
    public function badDI(): void
    {
        $env = ['FOO' => 'fooval', 'BAR' => 'barval'];
        $actionRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'action']);
        $this->expectException(\RuntimeException::class);
        new Application(
            env: $env,
            actionRoot: $actionRoot,
            providers: [ApplicationProvider::class],
            middlewares: [ExampleMiddlewareA::class, ExampleMiddlewareB::class, ExampleMiddlewareC::class],
        );
    }
}
