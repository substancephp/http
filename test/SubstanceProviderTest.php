<?php

declare(strict_types=1);

namespace Test;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Application;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\Environment;
use SubstancePHP\HTTP\EnvironmentInterface;
use SubstancePHP\HTTP\ErrorResponseFallbackGeneratorInterface;
use SubstancePHP\HTTP\Middleware\BodyParserMiddleware;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;
use SubstancePHP\HTTP\SubstanceProvider;
use TestUtil\Fixture\ContentTypeOverrideProvider;

#[CoversClass(SubstanceProvider::class)]
#[CoversMethod(SubstanceProvider::class, 'factories')]
class SubstanceProviderTest extends TestCase
{
    #[Test]
    public function factories(): void
    {
        $environment = new Environment(['HI' => 'cool']);
        $result = SubstanceProvider::factories($environment);
        foreach ($result as $value) {
            $this->assertInstanceOf(\Closure::class, $value);
        }

        $this->assertArrayHasKey(BodyParserMiddleware::class, $result);
        $this->assertArrayHasKey(ContextFactoryInterface::class, $result);
        $this->assertArrayHasKey(ContextFactoryInterface::class, $result);
        $this->assertArrayHasKey(EmitterInterface::class, $result);
        $this->assertArrayHasKey(EnvironmentInterface::class, $result);
        $this->assertArrayHasKey(ErrorResponseFallbackGeneratorInterface::class, $result);
        $this->assertArrayHasKey(ResponseFactoryInterface::class, $result);
        $this->assertArrayHasKey(ResponseFactoryInterface::class, $result);
        $this->assertArrayHasKey(RouteActorMiddleware::class, $result);
        $this->assertArrayHasKey(RouteMatcherMiddleware::class, $result);
        $this->assertArrayHasKey('substance.http.default-content-type', $result);
    }

    #[Test]
    public function defaultContentType(): void
    {
        $environment = new Environment([]);
        $container = Container::from(SubstanceProvider::factories($environment));
        $actual = $container->get('substance.http.default-content-type');
        $expected = 'text/html; charset=utf-8';
        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function defaultContentTypeOverridable(): void
    {
        $env = [];
        $actionRoot = \implode(
            \DIRECTORY_SEPARATOR,
            [\dirname(__DIR__), 'testutil', 'fixture', 'action'],
        );
        $templateRoot = \implode(
            \DIRECTORY_SEPARATOR,
            [\dirname(__DIR__), 'testutil', 'fixture', 'template'],
        );
        $instance = Application::make(
            env: $env,
            actionRoot: $actionRoot,
            templateRoot: $templateRoot,
            providers: [
                SubstanceProvider::class,
                ContentTypeOverrideProvider::class,
            ],
            middlewares: [],
            htmlEncoding: 'utf-8',
        );

        $this->assertSame('application/json', $instance->get('substance.http.default-content-type'));
    }
}
