<?php

declare(strict_types=1);

namespace Test\Middleware;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\Exception\BaseException\RoutingException;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\RendererFactory;
use SubstancePHP\HTTP\RequestHandler;
use SubstancePHP\HTTP\Respond;
use SubstancePHP\HTTP\Route;
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
        $context = Container::from([Respond::class => fn () => new Respond(200, 'application/json')]);
        $contextFactory->method('createContext')->willReturn($context);
        $responseFactory = new ResponseFactory();
        $templateRoot = \implode(DIRECTORY_SEPARATOR, [\dirname(__DIR__, 2), 'testutil', 'fixture', 'template']);
        $rendererFactory = new RendererFactory($templateRoot);
        return new RouteActorMiddleware($container, $contextFactory, $rendererFactory, $responseFactory);
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

        $route = Route::from(actionRoot: TestUtil::getActionFixtureRoot(), method: 'PATCH', path: '/inner/another');

        $request = $requestFactory
            ->createServerRequest('PATCH', '/inner/another')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"data":{"another route reached":true}}', (string) $response->getBody());
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
}
