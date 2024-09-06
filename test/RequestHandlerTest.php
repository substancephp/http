<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\EmptyMiddlewareStackException;
use SubstancePHP\HTTP\Exception\BaseException\UnexpectedRequestAttributeValueException;
use SubstancePHP\HTTP\Internal\MutableRequestHandler;
use SubstancePHP\HTTP\RequestHandler;
use SubstancePHP\HTTP\Route;
use TestUtil\Fixture\Middleware\AttributeGatheringMiddleware;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\TestUtil;

#[CoversClass(RequestHandler::class)]
#[CoversMethod(RequestHandler::class, 'from')]
#[CoversMethod(RequestHandler::class, 'handle')]
#[CoversClass(MutableRequestHandler::class)]
#[CoversMethod(MutableRequestHandler::class, '__construct')]
#[CoversMethod(MutableRequestHandler::class, 'handle')]
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
     * @param array<string> &$messages
     * @return array<MiddlewareInterface>
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
            '"SubstancePHP\\\\HTTP\\\\Route":{},' .
            '"middleware B called":true,' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);

        // test unhappy - bad route
        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, null);
        $requestHandler->handle($request);
    }
}
