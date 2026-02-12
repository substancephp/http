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
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\TestUtil;

#[CoversClass(Route::class)]
#[CoversMethod(Route::class, 'from')]
#[CoversMethod(Route::class, 'shouldSkip')]
#[CoversMethod(Route::class, 'execute')]
class RouteTest extends TestCase
{
    #[Test]
    public function from(): void
    {
        $actionRoot = TestUtil::getActionFixtureRoot();

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
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy');
        \assert($route instanceof Route);

        // As far as this method is concerned, the only thing that matters is whether the
        // middleware class name has been passed to the constructor.
        $this->assertTrue($route->shouldSkip(ExampleMiddlewareC::class));
        $this->assertTrue($route->shouldSkip(ExampleMiddlewareA::class));
        $this->assertFalse($route->shouldSkip(ExampleMiddlewareB::class));
        $this->assertFalse($route->shouldSkip('bye'));
    }

    #[Test]
    public function execute(): void
    {
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy');
        \assert($route instanceof Route);

        $context = Container::from(['greetWith' => fn () => 'buongiorno']);
        $out = $route->execute($context);
        $this->assertSame(['data' => ['greeting' => 'buongiorno']], $out);
    }
}
