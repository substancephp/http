<?php

declare(strict_types=1);

namespace Test\Middleware;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\Exception\BaseException\InvalidActionException;
use SubstancePHP\HTTP\Exception\BaseException\RoutingException;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Out;
use SubstancePHP\HTTP\RequestHandler;
use SubstancePHP\HTTP\Route;
use SubstancePHP\HTTP\Status;
use TestUtil\TestUtil;

#[CoversClass(RouteActorMiddleware::class)]
#[CoversMethod(RouteActorMiddleware::class, '__construct')]
#[CoversMethod(RouteActorMiddleware::class, 'process')]
class RouteActorMiddlewareTest extends TestCase
{
    private function makeInstance(): RouteActorMiddleware
    {
        $container = $this->createMock(ContainerInterface::class);
        $contextFactory = $this->createMock(ContextFactoryInterface::class);
        $context = Container::from([]);
        $contextFactory->expects($this->any())->method('createContext')->willReturn($context);
        $responseFactory = new ResponseFactory();
        return new RouteActorMiddleware($container, $contextFactory, $responseFactory);
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
    public function processUnhappyPathBadActionCallback(): void
    {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = Route::from(
            actionRoot: TestUtil::getActionFixtureRoot(),
            method: 'GET',
            path: '/dummy-bad-callback-return',
        );

        $request = $requestFactory
            ->createServerRequest('GET', '/dummy-back-callback-return')
            ->withAttribute(Route::class, $route);

        $this->expectException(InvalidActionException::class);
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
    #[DataProvider('provideMessageDataCombos')]
    public function processWithMessageDataCombos(
        Out $returnFromAction,
        string $expectedContent,
        int $expectedStatusCode,
    ): void {
        $requestFactory = new ServerRequestFactory();
        $requestHandler = $this->createMock(RequestHandler::class);
        $instance = $this->makeInstance();

        $route = $this->createMock(Route::class);
        $route->expects($this->once())->method('execute')->willReturn($returnFromAction);

        $request = $requestFactory->createServerRequest('GET', '/mocked')
            ->withAttribute(Route::class, $route);

        $response = $instance->process($request, $requestHandler);
        $this->assertSame($expectedContent, (string) $response->getBody());
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
    }

    /** @return array<array{Out, string, int}> */
    public static function provideMessageDataCombos(): array
    {
        return [
            [
                Out::noContent(),
                '',
                204,
            ],
            [
                Out::unauthorized(),
                '{"message":"Unauthorized"}',
                401,
            ],
            [
                Out::unauthorized('No can do'),
                '{"message":"No can do"}',
                401,
            ],
            [
                Out::data(['quantity' => 50]),
                '{"data":{"quantity":50}}',
                200,
            ],
            [
                Out::data(['quantity' => 50], Status::TEMPORARY_REDIRECT),
                '{"data":{"quantity":50}}',
                307,
            ],
        ];
    }
}
