<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\MiddlewareConfig;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;
use TestUtil\Fixture\Middleware\ConfigurableMiddlewareConfig;

#[CoversClass(MiddlewareConfig::class)]
#[CoversMethod(MiddlewareConfig::class, 'for')]
class MiddlewareConfigTest extends TestCase
{
    #[Test]
    public function for(): void
    {
        $config = new ConfigurableMiddlewareConfig(rate: 30);
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/')
            ->withAttribute(ConfigurableMiddleware::class, $config);

        $this->assertSame($config, MiddlewareConfig::for($request, ConfigurableMiddleware::class));
    }
}
