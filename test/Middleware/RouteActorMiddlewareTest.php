<?php

declare(strict_types=1);

namespace Test\Middleware;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\EmptyShare;
use SubstancePHP\HTTP\Exception\BaseException\RoutingException;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\RendererFactory;
use SubstancePHP\HTTP\Templating;
use SubstancePHP\HTTP\RequestHandler;
use SubstancePHP\HTTP\RequestParams\PathParams;
use SubstancePHP\HTTP\Respond;
use SubstancePHP\HTTP\Route;
use SubstancePHP\HTTP\Share;
use TestUtil\Fixture\GreetingShare;
use TestUtil\TestUtil;

#[CoversClass(RouteActorMiddleware::class)]
#[CoversMethod(RouteActorMiddleware::class, '__construct')]
#[CoversMethod(RouteActorMiddleware::class, 'process')]
#[AllowMockObjectsWithoutExpectations]
class RouteActorMiddlewareTest extends TestCase
{
    private function makeInstance(): RouteActorMiddleware
    {
        $container = $this->createMock(ContainerInterface::class);
        $contextFactory = $this->createStub(ContextFactoryInterface::class);
        $context = Container::from([
            Respond::class => function () {
                $respond = new Respond(200);
                $respond->setHeader('Content-Type', 'application/json');
                return $respond;
            },
        ]);
        $contextFactory->method('createContext')->willReturn($context);
        $responseFactory = new ResponseFactory();
        $templateRoot = TestUtil::getFixtureRoot() . '/template';
        $rendererFactory = new RendererFactory(new Templating($templateRoot, 'utf-8'));
        return new RouteActorMiddleware(
            $container,
            $contextFactory,
            $rendererFactory,
            $responseFactory,
            new Share(EmptyShare::class),
        );
    }

    #[Test]
    public function construct(): void
    {
        $instance = $this->makeInstance();
        $this->assertInstanceOf(RouteActorMiddleware::class, $instance);
    }

    #[Test]
    public function processHappyPath(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'PATCH',
            path: '/inner/another',
        );

        $request = $requestFactory
            ->createServerRequest('PATCH', '/inner/another')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"data":{"another route reached":true}}', (string) $response->getBody());
    }

    #[Test]
    public function processHappyPathHtmlTemplateWithPathParams(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/html-stores/42',
        );
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/html-stores/42')
            ->withAttribute(Route::class, $route);

        // The action opts into HTML rendering via Respond; the template `html-stores/[id].html.php`
        // is resolved from the route's declared path, with the captured param available in the data.
        $context = Container::from([
            PathParams::class => fn () => PathParams::fromRequest($request),
            Respond::class => function () {
                $respond = new Respond(200);
                $respond->setHeader('Content-Type', 'application/json');
                return $respond;
            },
        ]);
        $contextFactory = $this->createStub(ContextFactoryInterface::class);
        $contextFactory->method('createContext')->willReturn($context);
        $instance = new RouteActorMiddleware(
            $this->createMock(ContainerInterface::class),
            $contextFactory,
            new RendererFactory(new Templating(TestUtil::getFixtureRoot() . '/template', 'utf-8')),
            new ResponseFactory(),
            new Share(EmptyShare::class),
        );

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertSame("<div id=\"layout\"><h1>Store 42</h1>\n</div>\n", (string) $response->getBody());
    }

    /**
     * A route whose path has dynamic segments renders the template named after the declared segments, which
     * may be nested (e.g. `accounts/[id]/posts/[postId].html.php`), with the captured params as data.
     *
     * @param string $path a request path that resolves to a parameterized action
     * @param string $normalizedPath the route's declared path, which is also the template path
     * @param string $expected a substring of the rendered body
     */
    #[Test]
    #[TestWith(['/accounts/42/profile', 'accounts/[id]/profile', 'Profile 42'])]
    #[TestWith(['/accounts/42/posts/99', 'accounts/[id]/posts/[postId]', 'Post 99 of 42'])]
    public function processRendersTemplatesInNestedParameterizedDirectories(
        string $path,
        string $normalizedPath,
        string $expected,
    ): void {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);

        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', $path);
        \assert($route instanceof Route);
        $this->assertSame($normalizedPath, $route->normalizedPath);

        $request = $requestFactory
            ->createServerRequest('GET', $path)
            ->withAttribute(Route::class, $route);

        $context = Container::from([
            PathParams::class => fn () => PathParams::fromRequest($request),
            Respond::class => function () {
                $respond = new Respond(200);
                $respond->setHeader('Content-Type', 'text/html');
                return $respond;
            },
        ]);
        $contextFactory = $this->createStub(ContextFactoryInterface::class);
        $contextFactory->method('createContext')->willReturn($context);
        $instance = new RouteActorMiddleware(
            $this->createMock(ContainerInterface::class),
            $contextFactory,
            new RendererFactory(new Templating(TestUtil::getFixtureRoot() . '/template', 'utf-8')),
            new ResponseFactory(),
            new Share(EmptyShare::class),
        );

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[Test]
    public function processMergesSharedVariablesIntoTheTemplate(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);

        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/shared-vars');
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/shared-vars')
            ->withAttribute(Route::class, $route)
            ->withHeader('X-Who', 'World');

        $context = Container::from([
            Respond::class => function () {
                $respond = new Respond(200);
                $respond->setHeader('Content-Type', 'text/html');
                return $respond;
            },
            GreetingShare::class => fn () => new GreetingShare($request),
        ]);
        $contextFactory = $this->createStub(ContextFactoryInterface::class);
        $contextFactory->method('createContext')->willReturn($context);
        $instance = new RouteActorMiddleware(
            $this->createMock(ContainerInterface::class),
            $contextFactory,
            new RendererFactory(new Templating(TestUtil::getFixtureRoot() . '/template', 'utf-8')),
            new ResponseFactory(),
            new Share(GreetingShare::class),
        );

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<p>My App / World</p>', (string) $response->getBody());
    }

    #[Test]
    public function processRendersTheActionSelectedTemplate(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);

        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/custom-template');
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/custom-template')
            ->withAttribute(Route::class, $route);

        $context = Container::from([
            Respond::class => function () {
                $respond = new Respond(200);
                $respond->setHeader('Content-Type', 'text/html');
                return $respond;
            },
        ]);
        $contextFactory = $this->createStub(ContextFactoryInterface::class);
        $contextFactory->method('createContext')->willReturn($context);
        $instance = new RouteActorMiddleware(
            $this->createMock(ContainerInterface::class),
            $contextFactory,
            new RendererFactory(new Templating(TestUtil::getFixtureRoot() . '/template', 'utf-8')),
            new ResponseFactory(),
            new Share(EmptyShare::class),
        );

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        // The action's setTemplate('custom-template-alt') wins over the route path's custom-template.
        $body = (string) $response->getBody();
        $this->assertStringContainsString('OVERRIDE: World', $body);
        $this->assertStringNotContainsString('DEFAULT', $body);
    }

    #[Test]
    public function processUnhappyPathNoRoute(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $request = $requestFactory->createServerRequest('PATCH', '/inner/another');
        $this->expectException(RoutingException::class);
        $instance->process($request, $requestHandler);
    }

    #[Test]
    public function processHappyPathNoContentResponse(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/dummy-no-content',
        );

        $request = $requestFactory
            ->createServerRequest('GET', '/dummy-no-content')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
    }

    #[Test]
    public function processHappyPathValidationErrorResponse(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'PUT',
            path: '/dummy-unprocessable',
        );

        $request = $requestFactory
            ->createServerRequest('PUT', '/dummy-unprocessable')
            ->withAttribute(Route::class, $route);
        $request->getBody()->write('{"hello":"world"}');

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('{"message":"Invalid request body"}', (string) $response->getBody());
    }

    #[Test]
    public function processHappyPathCustomHeaders(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/custom-headers',
        );
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/custom-headers')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('one', $response->getHeaderLine('X-Single'));
        $this->assertSame(['a', 'b'], $response->getHeader('X-Multi'));
        $this->assertSame('{"data":{"ok":true}}', (string) $response->getBody());
    }

    #[Test]
    public function processHappyPathRedirect(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/redirect',
        );
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/redirect')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('/stores/new', $response->getHeaderLine('Location'));
        // Removing the content type means no body is rendered and no content type is emitted.
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertEmpty((string) $response->getBody());
    }

    #[Test]
    public function processHappyPathNoContentKeepsHeaders(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/no-content-with-header',
        );
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/no-content-with-header')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertEmpty((string) $response->getBody());
    }

    #[Test]
    public function processHappyPathNoContentTypeIsBodyless(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/no-content-type',
        );
        \assert($route instanceof Route);

        $request = $requestFactory
            ->createServerRequest('GET', '/no-content-type')
            ->withAttribute(Route::class, $route);

        // With no `Content-Type`, no renderer runs: the response is bodyless, not an error (it would
        // be an UnsupportedContentTypeException if an unrecognised content type were still set).
        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertEmpty((string) $response->getBody());
    }
}
