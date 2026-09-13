<?php

declare(strict_types=1);

namespace Test\Middleware;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SubstancePHP\HTTP\Middleware\Engage;

#[CoversClass(Engage::class)]
#[CoversMethod(Engage::class, '__construct')]
class EngageTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $engage = new Engage('alpha', 'beta', 'gamma');
        $this->assertSame(['alpha', 'beta', 'gamma'], $engage->engagedMiddlewares);
    }

    #[Test]
    public function useAsAttribute(): void
    {
        $closure = #[Engage('hi', 'there')] function () {
        };
        $function = new \ReflectionFunction($closure);
        $attributes = $function->getAttributes(Engage::class);
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0];
        $instance = $attribute->newInstance();
        $this->assertInstanceOf(Engage::class, $instance);
        $this->assertSame(['hi', 'there'], $instance->engagedMiddlewares);
    }
}
