<?php

declare(strict_types=1);

namespace Test\Middleware;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SubstancePHP\HTTP\Middleware\Skip;

#[CoversClass(Skip::class)]
#[CoversMethod(Skip::class, '__construct')]
class SkipTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $skip = new Skip('alpha', 'beta', 'gamma');
        $this->assertSame(['alpha', 'beta', 'gamma'], $skip->skippableMiddlewares);
    }

    #[Test]
    public function useAsAttribute(): void
    {
        $closure = #[Skip('hi', 'there')] function () {
        };
        $function = new \ReflectionFunction($closure);
        $attributes = $function->getAttributes(Skip::class);
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0];
        $instance = $attribute->newInstance();
        $this->assertInstanceOf(Skip::class, $instance);
        $this->assertSame(['hi', 'there'], $instance->skippableMiddlewares);
    }
}
