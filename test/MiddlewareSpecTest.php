<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\MiddlewareSpec;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;

#[CoversClass(MiddlewareSpec::class)]
#[CoversMethod(MiddlewareSpec::class, '__construct')]
#[CoversMethod(MiddlewareSpec::class, 'enable')]
#[CoversMethod(MiddlewareSpec::class, 'disable')]
class MiddlewareSpecTest extends TestCase
{
    #[Test]
    public function enable(): void
    {
        $spec = MiddlewareSpec::enable(ExampleMiddlewareA::class);
        $this->assertSame(ExampleMiddlewareA::class, $spec->class);
        $this->assertTrue($spec->enabledByDefault);
    }

    #[Test]
    public function disable(): void
    {
        $spec = MiddlewareSpec::disable(ExampleMiddlewareB::class);
        $this->assertSame(ExampleMiddlewareB::class, $spec->class);
        $this->assertFalse($spec->enabledByDefault);
    }
}
