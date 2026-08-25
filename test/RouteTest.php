<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactory;
use SubstancePHP\HTTP\Route;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\TestUtil;

#[CoversClass(Route::class)]
#[CoversMethod(Route::class, 'from')]
#[CoversMethod(Route::class, 'getParams')]
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

        // The root route resolves the `_root` sentinel file.
        $route = Route::from($actionRoot, 'GET', '/');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('_root', $route->normalizedPath);

        // "0" is falsy in PHP, but it is a real path segment — not the root.
        $route = Route::from($actionRoot, 'GET', '/0');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('0', $route->normalizedPath);

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
    public function pathParams(): void
    {
        $actionRoot = TestUtil::getActionFixtureRoot();

        // A param file captures the leaf segment under its declared name.
        $route = Route::from($actionRoot, 'GET', '/stores/42');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['id' => '42'], $route->getParams());
        // normalizedPath is the declared path, which is also the HTML template path.
        $this->assertSame('stores/[id]', $route->normalizedPath);

        // The captured value is URL-decoded.
        $route = Route::from($actionRoot, 'GET', '/stores/foo%20bar');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['id' => 'foo bar'], $route->getParams());

        // A literal file beats a param: `/stores/new` hits `stores/new.get.php`.
        $route = Route::from($actionRoot, 'GET', '/stores/new');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame([], $route->getParams());
        $this->assertSame('stores/new', $route->normalizedPath);

        // A param route can be executed, with the params injected via PathParams.
        $route = Route::from($actionRoot, 'GET', '/stores/42');
        \assert($route instanceof Route);
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/stores/42')
            ->withAttribute(Route::class, $route);
        $context = (new ContextFactory())->createContext(Container::from([]), $request);
        $out = $route->execute($context);
        $this->assertSame(['data' => ['id' => '42']], $out);

        // A param never matches across a missing intermediate directory.
        $this->assertNull(Route::from($actionRoot, 'GET', '/stores/deeper/42'));

        // A param never matches a different HTTP method.
        $this->assertNull(Route::from($actionRoot, 'POST', '/stores/42'));

        // An empty or dot segment matches neither a literal nor a parameter.
        $this->assertNull(Route::from($actionRoot, 'GET', '/stores//42'));
        $this->assertNull(Route::from($actionRoot, 'GET', '/stores/.'));
        $this->assertNull(Route::from($actionRoot, 'GET', '/stores/..'));

        // A parameterless directory with no matching file still 404s.
        $this->assertNull(Route::from($actionRoot, 'GET', '/inner/42'));
    }

    #[Test]
    public function pathParamsInIntermediateSegments(): void
    {
        $actionRoot = TestUtil::getActionFixtureRoot();

        // An intermediate segment may be a param directory, with a literal leaf below it.
        $route = Route::from($actionRoot, 'GET', '/acme/store');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['chain' => 'acme'], $route->getParams());
        $this->assertSame('[chain]/store', $route->normalizedPath);

        // Intermediate param values are URL-decoded too.
        $route = Route::from($actionRoot, 'GET', '/foo%20bar/store');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['chain' => 'foo bar'], $route->getParams());

        // Multiple segments may be params: an intermediate param plus a leaf param.
        $route = Route::from($actionRoot, 'GET', '/accounts/42/posts/99');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['id' => '42', 'postId' => '99'], $route->getParams());
        $this->assertSame('accounts/[id]/posts/[postId]', $route->normalizedPath);

        // Adjacent dynamic segments work too: a param directory followed by a param file.
        $route = Route::from($actionRoot, 'GET', '/orgs/foo/bar');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['org' => 'foo', 'repo' => 'bar'], $route->getParams());

        // A multi-param route can be executed, with all params injected via PathParams.
        $route = Route::from($actionRoot, 'GET', '/accounts/42/posts/99');
        \assert($route instanceof Route);
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/accounts/42/posts/99')
            ->withAttribute(Route::class, $route);
        $context = (new ContextFactory())->createContext(Container::from([]), $request);
        $out = $route->execute($context);
        $this->assertSame(['data' => ['id' => '42', 'postId' => '99']], $out);

        // A literal intermediate directory is never revisited as a param when the leaf misses.
        $this->assertNull(Route::from($actionRoot, 'GET', '/inner/store'));

        // A param directory does not match a leaf file of a different HTTP method.
        $this->assertNull(Route::from($actionRoot, 'POST', '/acme/store'));
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
