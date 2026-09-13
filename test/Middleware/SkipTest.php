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
        $skip = new Skip('alpha');
        $this->assertSame('alpha', $skip->middleware);
    }

    #[Test]
    public function useAsAttribute(): void
    {
        // The attribute is repeatable: one middleware per occurrence.
        $closure = #[Skip('alpha')] #[Skip('beta')] function () {
        };
        $function = new \ReflectionFunction($closure);
        $attributes = $function->getAttributes(Skip::class);
        $this->assertCount(2, $attributes);
        $this->assertSame('alpha', $attributes[0]->newInstance()->middleware);
        $this->assertSame('beta', $attributes[1]->newInstance()->middleware);
    }
}
