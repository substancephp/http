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
        $engage = new Engage('alpha');
        $this->assertSame('alpha', $engage->middleware);
    }

    #[Test]
    public function useAsAttribute(): void
    {
        // The attribute is repeatable: one middleware per occurrence.
        $closure = #[Engage('alpha')] #[Engage('beta')] function () {
        };
        $function = new \ReflectionFunction($closure);
        $attributes = $function->getAttributes(Engage::class);
        $this->assertCount(2, $attributes);
        $this->assertSame('alpha', $attributes[0]->newInstance()->middleware);
        $this->assertSame('beta', $attributes[1]->newInstance()->middleware);
    }
}
