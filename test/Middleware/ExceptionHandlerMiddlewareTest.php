<?php

declare(strict_types=1);

namespace Test\Middleware;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use SubstancePHP\HTTP\ErrorResponseFallbackGenerator;
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Middleware\ExceptionHandlerMiddleware;

#[CoversClass(ExceptionHandlerMiddleware::class)]
#[CoversMethod(ExceptionHandlerMiddleware::class, '__construct')]
#[CoversMethod(ExceptionHandlerMiddleware::class, 'process')]
class ExceptionHandlerMiddlewareTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, null),
        );
        $this->assertInstanceOf(ExceptionHandlerMiddleware::class, $instance);
    }

    #[Test]
    public function processUserError(): void
    {
        // setup

        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                UserError::throw(404);
            }
        };

        $loggedMessages = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')
            ->willReturnCallback(function (string $message) use (&$loggedMessages): void {
                $loggedMessages[] = $message;
            });

        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, null),
            $logger,
        );

        // tests

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $instance->process($request, $requestHandler);

        $correlationId = $response->getHeaderLine('X-Request-Id');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', (string) $response->getBody());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertNotSame('', $correlationId);
        $this->assertCount(1, $loggedMessages);
        $this->assertStringContainsString("[$correlationId]", $loggedMessages[0]);
    }

    #[Test]
    public function processUserErrorRendersJsonWhenClientAcceptsJson(): void
    {
        // setup

        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                UserError::throw(404);
            }
        };

        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, null),
        );

        // tests

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');
        $response = $instance->process($request, $requestHandler);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('{"error":"Not Found"}', (string) $response->getBody());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertNotSame('', $response->getHeaderLine('X-Request-Id'));
    }

    #[Test]
    public function processUserErrorRendersHtmlWhenClientAcceptsHtml(): void
    {
        // setup

        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                UserError::throw(418, '<script>alert(1)</script>');
            }
        };

        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, null),
        );

        // tests

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/html');
        $response = $instance->process($request, $requestHandler);

        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $response->getBody());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertNotSame('', $response->getHeaderLine('X-Request-Id'));
    }

    #[Test]
    public function processUserErrorNegotiatesInClientPreferenceOrder(): void
    {
        // setup

        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                UserError::throw(404);
            }
        };

        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, null),
        );

        $requestFactory = new ServerRequestFactory();

        // tests

        // first-listed preference wins; duplicates and q-value parameters are ignored
        $request = $requestFactory->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/html, application/json, text/html;q=0.5, APPLICATION/JSON');
        $response = $instance->process($request, $requestHandler);
        $this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $request = $requestFactory->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json, text/html, application/json');
        $response = $instance->process($request, $requestHandler);
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"error":"Not Found"}', (string) $response->getBody());

        // matching is case-insensitive
        $request = $requestFactory->createServerRequest('GET', '/')
            ->withHeader('Accept', 'APPLICATION/JSON');
        $response = $instance->process($request, $requestHandler);
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function processGenericException(): void
    {
        // setup

        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('boom');
            }
        };

        $loggedMessages = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')
            ->willReturnCallback(function (string $message) use (&$loggedMessages): void {
                $loggedMessages[] = $message;
            });

        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, $logger),
            $logger,
        );

        // tests

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $instance->process($request, $requestHandler);

        $correlationId = $response->getHeaderLine('X-Request-Id');
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        // the exception message must not leak to the client
        $this->assertSame('Internal Server Error', (string) $response->getBody());
        $this->assertNotSame('', $correlationId);
        $this->assertCount(1, $loggedMessages);
        $this->assertStringContainsString('LogicException', $loggedMessages[0]);
        $this->assertStringContainsString("[$correlationId]", $loggedMessages[0]);
    }

    #[Test]
    public function processGenericExceptionRendersJsonWhenClientAcceptsJson(): void
    {
        // setup

        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('boom');
            }
        };

        $responseFactory = new ResponseFactory();
        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, null),
        );

        // tests

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');
        $response = $instance->process($request, $requestHandler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{"error":"Internal Server Error"}', (string) $response->getBody());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertNotSame('', $response->getHeaderLine('X-Request-Id'));
    }

    #[Test]
    public function processHappyPath(): void
    {
        // setup

        $responseFactory = new ResponseFactory();
        $expectedResponse = $responseFactory->createResponse(200);
        $expectedResponse->getBody()->write('hello');

        $requestHandler = new class ($expectedResponse) implements RequestHandlerInterface {
            public ?string $correlationId = null;

            public function __construct(private ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->correlationId = $request->getAttribute(
                    ExceptionHandlerMiddleware::CORRELATION_ID_ATTRIBUTE,
                );
                return $this->response;
            }
        };

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $logger->expects($this->never())->method('error');

        $instance = new ExceptionHandlerMiddleware(
            $responseFactory,
            new ErrorResponseFallbackGenerator($responseFactory, $logger),
            $logger,
        );

        // tests

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $instance->process($request, $requestHandler);

        $this->assertNotSame('', $requestHandler->correlationId);
        $this->assertSame($requestHandler->correlationId, $response->getHeaderLine('X-Request-Id'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('hello', (string) $response->getBody());
    }
}
