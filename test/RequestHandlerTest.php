<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\EmptyMiddlewareStackException;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;
use SubstancePHP\HTTP\Internal\MutableRequestHandler;
use SubstancePHP\HTTP\RequestHandler;
use SubstancePHP\HTTP\Route;
use TestUtil\Fixture\Middleware\AttributeGatheringMiddleware;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;
use TestUtil\TestUtil;

#[CoversClass(RequestHandler::class)]
#[CoversMethod(RequestHandler::class, 'from')]
#[CoversMethod(RequestHandler::class, 'handle')]
#[CoversClass(MutableRequestHandler::class)]
#[CoversMethod(MutableRequestHandler::class, '__construct')]
#[CoversMethod(MutableRequestHandler::class, 'handle')]
#[AllowMockObjectsWithoutExpectations]
class RequestHandlerTest extends TestCase
{
    #[Test]
    public function fromAndHandleHappyPath(): void
    {
        $messages = [];
        $middlewares = $this->getDummyMiddlewares($messages);
        $requestHandler = RequestHandler::from($middlewares);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $response = $requestHandler->handle($mockRequest);
        $this->assertCount(4, $messages);
        $this->assertEquals(418, $response->getStatusCode());
        $this->assertSame([
            'middlewareA called before calling handler',
            'middlewareC called before calling handler',
            'middlewareC called after calling handler',
            'middlewareB called after calling handler',
        ], $messages);
    }

    #[Test]
    public function handleWithEmptyMiddlewares(): void
    {
        $requestHandler = RequestHandler::from([]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $this->expectException(EmptyMiddlewareStackException::class);
        $requestHandler->handle($mockRequest);
    }

    #[Test]
    public function handleWithoutResponseReturnedFromMiddlewares(): void
    {
        $messages = [];
        [$middlewareA, $middlewareB, $middlewareC] = $this->getDummyMiddlewares($messages);
        $requestHandler = RequestHandler::from([$middlewareA, $middlewareB, $middlewareC]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $this->expectException(\RuntimeException::class);
        $requestHandler->handle($mockRequest);
    }

    /**
     * @param string[] &$messages
     * @return MiddlewareInterface[]
     */
    private function getDummyMiddlewares(array &$messages): array
    {
        $middlewareA = new class ($messages) implements MiddlewareInterface {
            /** @param array<string> &$messages */
            public function __construct(public array &$messages)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $this->messages[] = 'middlewareA called before calling handler';
                return $handler->handle($request->withAttribute('middlewareA called', true));
            }
        };
        $middlewareB = new class ($messages) implements MiddlewareInterface {
            /** @param array<string> &$messages */
            public function __construct(public array &$messages)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request->withAttribute('middlewareB called', true));
                $this->messages[] = 'middlewareB called after calling handler';
                return $response;
            }
        };
        $middlewareC = new class ($messages) implements MiddlewareInterface {
            /** @param array<string> &$messages */
            public function __construct(public array &$messages)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $this->messages[] = 'middlewareC called before calling handler';
                $response = $handler->handle($request->withAttribute('middlewareC called', true));
                $this->messages[] = 'middlewareC called after calling handler';
                return $response;
            }
        };
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->atMost(1))->method('getStatusCode')->willReturn(418);
        $middlewareD = new class ($messages, $mockResponse) implements MiddlewareInterface {
            /** @param array<string> &$messages */
            public function __construct(public array &$messages, private ResponseInterface $mockResponse)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $this->mockResponse;
            }
        };
        $middlewareE = new class ($messages) implements MiddlewareInterface {
            /** @param array<string> &$messages */
            public function __construct(public array &$messages)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                // We're expecting this not to be reached, assuming middlewareD comes first.
                $this->messages[] = 'middlewareE called before calling handler';
                $response = $handler->handle($request->withAttribute('middlewareE called', true));
                $this->messages[] = 'middlewareE called after calling handler';
                return $response;
            }
        };

        return [$middlewareA, $middlewareB, $middlewareC, $middlewareD, $middlewareE];
    }

    #[Test]
    public function handleWithSkippedMiddlewares(): void
    {
        // setup
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $requestHandler = RequestHandler::from([
            new ExampleMiddlewareA(),
            new ExampleMiddlewareB(),
            new ExampleMiddlewareC(),
            new AttributeGatheringMiddleware($responseFactory),
        ]);
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        // test happy
        $response = $requestHandler->handle($request);
        $requestAttributes = $response->getHeader('X-Request-Attributes');
        $this->assertCount(1, $requestAttributes);
        $expected = '{' .
            '"SubstancePHP\\\\HTTP\\\\Route":{"normalizedPath":"dummy"},' .
            '"middleware B called":true,' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);

        // test unhappy - bad route
        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, null);
        $requestHandler->handle($request);
    }

    #[Test]
    public function handleWithDefaultSkippedMiddleware(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        // B is registered but disabled by default; /dummy does not engage it.
        $requestHandler = RequestHandler::from(
            [
                new ExampleMiddlewareA(),
                new ExampleMiddlewareB(),
                new ExampleMiddlewareC(),
                new AttributeGatheringMiddleware($responseFactory),
            ],
            [ExampleMiddlewareB::class],
        );
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $response = $requestHandler->handle($request);
        $requestAttributes = $response->getHeader('X-Request-Attributes');
        $this->assertCount(1, $requestAttributes);
        // /dummy declares #[Skip(A, C)]; B is disabled by default, so only gathering runs.
        $expected = '{' .
            '"SubstancePHP\\\\HTTP\\\\Route":{"normalizedPath":"dummy"},' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);
    }

    #[Test]
    public function handleWithEngagedMiddleware(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        // B is disabled by default, but /dummy-engage opts it back in.
        $requestHandler = RequestHandler::from(
            [
                new ExampleMiddlewareA(),
                new ExampleMiddlewareB(),
                new ExampleMiddlewareC(),
                new AttributeGatheringMiddleware($responseFactory),
            ],
            [ExampleMiddlewareB::class],
        );
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy-engage');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $response = $requestHandler->handle($request);
        $requestAttributes = $response->getHeader('X-Request-Attributes');
        $this->assertCount(1, $requestAttributes);
        $expected = '{' .
            '"SubstancePHP\\\\HTTP\\\\Route":{"normalizedPath":"dummy-engage"},' .
            '"middleware A called":true,' .
            '"middleware B called":true,' .
            '"middleware C called":true,' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);
    }

    #[Test]
    public function handleWithUnregisteredMiddlewareDeclaration(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();
        $requestHandler = RequestHandler::from([new AttributeGatheringMiddleware($responseFactory)]);
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy-unknown');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $this->expectException(InvalidMiddlewareException::class);
        $requestHandler->handle($request);
    }

    #[Test]
    public function handleWithConfiguredMiddleware(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        // ConfigurableMiddleware is disabled by default; #[Configure] both configures and engages it.
        $requestHandler = RequestHandler::from(
            [new ConfigurableMiddleware(), new AttributeGatheringMiddleware($responseFactory)],
            [ConfigurableMiddleware::class],
        );
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy-configured');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $response = $requestHandler->handle($request);
        $requestAttributes = $response->getHeader('X-Request-Attributes');
        $this->assertCount(1, $requestAttributes);
        $expected = '{' .
            '"SubstancePHP\\\\HTTP\\\\Route":{"normalizedPath":"dummy-configured"},' .
            '"TestUtil\\\\Fixture\\\\Middleware\\\\ConfigurableMiddleware":{"rate":30},' .
            '"configurable middleware rate":30,' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);
    }

    #[Test]
    public function handleWithUnconfiguredMiddlewareUsesDefaults(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        // No #[Configure] on /0: the middleware still receives parseConfig([]) (its defaults).
        $requestHandler = RequestHandler::from(
            [new ConfigurableMiddleware(), new AttributeGatheringMiddleware($responseFactory)],
        );
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/0');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $response = $requestHandler->handle($request);
        $requestAttributes = $response->getHeader('X-Request-Attributes');
        $expected = '{' .
            '"SubstancePHP\\\\HTTP\\\\Route":{"normalizedPath":"0"},' .
            '"TestUtil\\\\Fixture\\\\Middleware\\\\ConfigurableMiddleware":{"rate":10},' .
            '"configurable middleware rate":10,' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);
    }

    #[Test]
    public function handleWithUnregisteredConfiguration(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();
        $requestHandler = RequestHandler::from([new AttributeGatheringMiddleware($responseFactory)]);
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy-configured-unknown');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $this->expectException(InvalidMiddlewareException::class);
        $requestHandler->handle($request);
    }

    #[Test]
    public function handleWithNonConfigurableConfiguration(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();
        // ExampleMiddlewareA is registered, but does not implement ConfigurableMiddlewareInterface.
        $requestHandler = RequestHandler::from(
            [new ExampleMiddlewareA(), new AttributeGatheringMiddleware($responseFactory)],
        );
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/dummy-configured-nonconfigurable');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        $this->expectException(InvalidMiddlewareException::class);
        $requestHandler->handle($request);
    }
}
