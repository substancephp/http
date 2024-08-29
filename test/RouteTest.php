<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Out;
use SubstancePHP\HTTP\Route;

#[CoversClass(Route::class)]
#[CoversMethod(Route::class, 'from')]
#[CoversMethod(Route::class, 'shouldSkip')]
#[CoversMethod(Route::class, 'execute')]
class RouteTest extends TestCase
{
    #[Test]
    public function from(): void
    {
        $actionRoot = dirname(__DIR__) . '/testutil/Fixture';

        $route = Route::from($actionRoot, 'GET', '/dummy');
        $this->assertInstanceOf(Route::class, $route);

        $route = Route::from($actionRoot, 'POST', '/dummy');
        $this->assertNull($route);

        $route = Route::from($actionRoot, 'GET', '/non-existent');
        $this->assertNull($route);

        $route = Route::from('alskdjflaksdjflaksjdf', 'GET', '/dummy');
        $this->assertNull($route);

        $route = Route::from($actionRoot, 'GET', '/dummy-bad');
        $this->assertNull($route);
    }

    #[Test]
    public function shouldSkip(): void
    {
        $actionRoot = dirname(__DIR__) . '/testutil/Fixture';
        $route = Route::from($actionRoot, 'GET', '/dummy');
        \assert($route instanceof Route);

        $this->assertTrue($route->shouldSkip('hi'));
        $this->assertTrue($route->shouldSkip('there'));
        $this->assertFalse($route->shouldSkip('bye'));
    }

    #[Test]
    public function execute(): void
    {
        $actionRoot = dirname(__DIR__) . '/testutil/Fixture';
        $route = Route::from($actionRoot, 'GET', '/dummy');
        \assert($route instanceof Route);

        $context = Container::from(['greetWith' => fn () => 'buongiorno']);
        $out = $route->execute($context);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(['greeting' => 'buongiorno'], $out->getData());
    }
}
