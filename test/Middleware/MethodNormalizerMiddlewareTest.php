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
use SubstancePHP\HTTP\Middleware\MethodNormalizerMiddleware;

#[CoversClass(MethodNormalizerMiddleware::class)]
#[CoversMethod(MethodNormalizerMiddleware::class, 'process')]
class MethodNormalizerMiddlewareTest extends TestCase
{
    #[Test]
    public function process(): void
    {
        // setup

        $requestFactory = new ServerRequestFactory();

        $requestHandler = new class implements RequestHandlerInterface {
            public ?string $method = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->method = $request->getMethod();
                $responseFactory = new ResponseFactory();
                return $responseFactory->createResponse();
            }
        };

        $instance = new MethodNormalizerMiddleware();

        // tests

        $request = $requestFactory->createServerRequest('GET', '/');
        $instance->process($request, $requestHandler);
        $this->assertSame('GET', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/');
        $instance->process($request, $requestHandler);
        $this->assertSame('POST', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/')->withParsedBody(['_METHOD' => 'PUT']);
        $instance->process($request, $requestHandler);
        $this->assertSame('PUT', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/')->withParsedBody(['_method' => 'DELETE']);
        $instance->process($request, $requestHandler);
        $this->assertSame('DELETE', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/')->withParsedBody(['method' => 'PUT']);
        $instance->process($request, $requestHandler);
        $this->assertSame('POST', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/')->withHeader('x-http-method-override', 'PATCH');
        $instance->process($request, $requestHandler);
        $this->assertSame('PATCH', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/')->withHeader('X-HTTP-METHOD-OVERRIDE', 'PATCH');
        $instance->process($request, $requestHandler);
        $this->assertSame('PATCH', $requestHandler->method);

        $request = $requestFactory->createServerRequest('POST', '/')->withHeader('XHTTPMETHODOVERRIDE', 'PATCH');
        $instance->process($request, $requestHandler);
        $this->assertSame('POST', $requestHandler->method);
    }
}
