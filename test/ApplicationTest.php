<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Application;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;
use SubstancePHP\HTTP\MiddlewareSpec;
use SubstancePHP\HTTP\SubstanceProvider;
use SubstancePHP\HTTP\Templating;
use TestUtil\Fixture\ApplicationProvider;
use TestUtil\Fixture\Middleware\AttributeGatheringMiddleware;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;
use TestUtil\TestUtil;

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
        $templateRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'template']);
        $instance = Application::make(
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
            templating: new Templating($templateRoot),
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
        $templateRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'template']);
        $this->expectException(\RuntimeException::class);
        Application::make(
            env: $env,
            actionRoot: $actionRoot,
            providers: [ApplicationProvider::class],
            middlewares: [ExampleMiddlewareA::class, ExampleMiddlewareB::class, ExampleMiddlewareC::class],
            templating: new Templating($templateRoot),
        );
    }

    #[Test]
    public function duplicateMiddlewareThrows(): void
    {
        $actionRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'action']);
        $templateRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'template']);
        $this->expectException(InvalidMiddlewareException::class);
        Application::make(
            env: [],
            actionRoot: $actionRoot,
            providers: [SubstanceProvider::class, ApplicationProvider::class],
            middlewares: [ExampleMiddlewareA::class, ExampleMiddlewareA::class],
            templating: new Templating($templateRoot),
        );
    }

    #[Test]
    public function disabledByDefaultMiddlewareIsAccepted(): void
    {
        $actionRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'action']);
        $templateRoot = \implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'testutil', 'fixture', 'template']);
        $instance = Application::make(
            env: [],
            actionRoot: $actionRoot,
            providers: [SubstanceProvider::class, ApplicationProvider::class],
            middlewares: [
                MiddlewareSpec::disable(ExampleMiddlewareA::class),
                ExampleMiddlewareB::class,
            ],
            templating: new Templating($templateRoot),
        );
        $this->assertInstanceOf(Application::class, $instance);
    }

    #[Test]
    public function registersTheTemplatingConfiguration(): void
    {
        $templating = new Templating(root: TestUtil::getFixtureRoot() . '/template');
        $instance = Application::make(
            env: [],
            actionRoot: TestUtil::getActionFixtureRoot(),
            providers: [SubstanceProvider::class],
            middlewares: [],
            templating: $templating,
        );
        $this->assertSame($templating, $instance->get(Templating::class));
    }
}
