<?php

declare(strict_types=1);

namespace Test;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\Environment;
use SubstancePHP\HTTP\EnvironmentInterface;
use SubstancePHP\HTTP\ErrorResponseFallbackGeneratorInterface;
use SubstancePHP\HTTP\Middleware\BodyParserMiddleware;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;
use SubstancePHP\HTTP\SubstanceProvider;

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
    }
}
