<?php

declare(strict_types=1);

namespace Test\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Middleware\Configure;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;

#[CoversClass(Configure::class)]
#[CoversMethod(Configure::class, '__construct')]
class ConfigureTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $configure = new Configure(ConfigurableMiddleware::class, ['rate' => 30]);
        $this->assertSame(ConfigurableMiddleware::class, $configure->middleware);
        $this->assertSame(['rate' => 30], $configure->config);
    }

    #[Test]
    public function useAsAttribute(): void
    {
        $closure =
            #[Configure(ConfigurableMiddleware::class, ['rate' => 1])]
            #[Configure(ConfigurableMiddleware::class, ['rate' => 2])]
            function () {
            };
        $attributes = (new \ReflectionFunction($closure))->getAttributes(Configure::class);
        $this->assertCount(2, $attributes);
        $this->assertSame(['rate' => 1], $attributes[0]->newInstance()->config);
        $this->assertSame(['rate' => 2], $attributes[1]->newInstance()->config);
    }
}
