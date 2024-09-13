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
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Middleware\BodyParserMiddleware;

#[CoversClass(BodyParserMiddleware::class)]
#[CoversMethod(BodyParserMiddleware::class, 'process')]
class BodyParserMiddlewareTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $instance = new BodyParserMiddleware();
        $this->assertInstanceOf(BodyParserMiddleware::class, $instance);
    }

    #[Test]
    public function process(): void
    {
        // setup

        $requestFactory = new ServerRequestFactory();

        $requestHandler = new class implements RequestHandlerInterface {
            /** @var array<mixed>|object|null  */
            public array|null|object $parsedBody = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->parsedBody = $request->getParsedBody();
                $responseFactory = new ResponseFactory();
                return $responseFactory->createResponse();
            }
        };

        $instance = new BodyParserMiddleware();

        // tests

        $request = $requestFactory->createServerRequest('POST', '/');
        $instance->process($request, $requestHandler);
        $this->assertNull($requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('POST', '/')->withParsedBody(['hi' => 'there']);
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody(['hi' => 'there']);
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write('{"hi":"there"}');
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody([]);
        $request->getBody()->write('{"hi":"there"}');
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('PUT', '/')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write('{"hi":"there"}');
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('PATCH', '/')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write('{"hi":"there"}');
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('DELETE', '/')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write('{"hi":"there"}');
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);

        $request = $requestFactory->createServerRequest('GET', '/')
            ->withHeader('Content-Type', 'application/json');
        $instance->process($request, $requestHandler);
        $this->assertNull($requestHandler->parsedBody);

        $this->expectException(UserError::class);
        $request = $requestFactory->createServerRequest('PATCH', '/')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write('invalid-json');
        $instance->process($request, $requestHandler);
        $this->assertSame(['hi' => 'there'], $requestHandler->parsedBody);
    }
}
