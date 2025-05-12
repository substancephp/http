<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Environment;

#[CoversClass(\SubstancePHP\HTTP\Environment::class)]
#[CoversMethod(\SubstancePHP\HTTP\Environment::class, '__construct')]
#[CoversMethod(\SubstancePHP\HTTP\Environment::class, 'get')]
class EnvironmentTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $data = ['foo' => 'bar', 'bar' => 'baz'];
        $instance = new Environment($data);
        $this->assertSame('bar', $instance->get('foo'));
        $this->assertSame('baz', $instance->get('bar'));
        $this->assertNull($instance->get('baz'));
        $this->assertNull($instance->get('cool'));
        $this->assertNull($instance->get(''));
    }
}
