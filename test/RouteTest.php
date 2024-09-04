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
use TestUtil\Fixture\Middleware\ExampleNonSkippableMiddleware;
use TestUtil\Fixture\Middleware\ExampleSkippableMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleSkippableMiddlewareB;

#[CoversClass(Route::class)]
#[CoversMethod(Route::class, 'from')]
#[CoversMethod(Route::class, 'shouldSkip')]
#[CoversMethod(Route::class, 'execute')]
class RouteTest extends TestCase
{
    private static function getActionRoot(): string
    {
        return dirname(__DIR__) . '/testutil/Fixture/action';
    }

    #[Test]
    public function from(): void
    {
        $actionRoot = self::getActionRoot();

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
        $route = Route::from(self::getActionRoot(), 'GET', '/dummy');
        \assert($route instanceof Route);

        // As far as this method is concerned, the only thing that matters is whether the
        // middleware class name has been passed to the constructor.
        $this->assertTrue($route->shouldSkip(ExampleNonSkippableMiddleware::class));
        $this->assertTrue($route->shouldSkip(ExampleSkippableMiddlewareA::class));
        $this->assertFalse($route->shouldSkip(ExampleSkippableMiddlewareB::class));
        $this->assertFalse($route->shouldSkip('bye'));
    }

    #[Test]
    public function execute(): void
    {
        $route = Route::from(self::getActionRoot(), 'GET', '/dummy');
        \assert($route instanceof Route);

        $context = Container::from(['greetWith' => fn () => 'buongiorno']);
        $out = $route->execute($context);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(['greeting' => 'buongiorno'], $out->getData());
    }
}
