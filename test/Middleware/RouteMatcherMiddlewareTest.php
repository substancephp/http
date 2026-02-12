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
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;
use SubstancePHP\HTTP\Route;
use TestUtil\TestUtil;

#[CoversClass(RouteMatcherMiddleware::class)]
#[CoversMethod(RouteMatcherMiddleware::class, '__construct')]
#[CoversMethod(RouteMatcherMiddleware::class, 'process')]
class RouteMatcherMiddlewareTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $instance = new RouteMatcherMiddleware('/hi/there');
        $this->assertInstanceOf(RouteMatcherMiddleware::class, $instance);
    }

    #[Test]
    public function process(): void
    {
        # setup
        $requestFactory = new ServerRequestFactory();

        $requestHandler = new class implements RequestHandlerInterface {
            public function __construct(public ?Route $route = null)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->route = $request->getAttribute(Route::class);
                \assert($this->route instanceof Route);
                $responseFactory = new ResponseFactory();
                return $responseFactory->createResponse();
            }
        };

        $instance = new RouteMatcherMiddleware(TestUtil::getActionFixtureRoot());

        # happy path
        (function () use ($instance, $requestFactory, $requestHandler): void {
            $request = $requestFactory->createServerRequest('GET', '/dummy');
            $instance->process($request, $requestHandler);
            $route = $requestHandler->route;
            $this->assertInstanceOf(Route::class, $route);
            $out = $route->execute(Container::from(['greetWith' => fn () => 'hi']));
            $this->assertSame(['data' => ['greeting' => 'hi']], $out);
        })();

        # another happy path
        (function () use ($instance, $requestFactory, $requestHandler): void {
            $request = $requestFactory->createServerRequest('patch', '/inner/another');
            $instance->process($request, $requestHandler);
            $route = $requestHandler->route;
            $this->assertInstanceOf(Route::class, $route);
            $out = $route->execute(Container::from([]));
            $this->assertSame(['data' => ['another route reached' => true]], $out);
        })();

        # unhappy path - wrong HTTP method
        (function () use ($instance, $requestFactory, $requestHandler): void {
            $this->expectException(UserError::class);
            $this->expectExceptionMessage('Not Found');
            $request = $requestFactory->createServerRequest('POST', '/dummy');
            $instance->process($request, $requestHandler);
        })();

        # another unhappy path - wrong path
        (function () use ($instance, $requestFactory, $requestHandler): void {
            $this->expectException(UserError::class);
            $this->expectExceptionMessage('Not Found');
            $request = $requestFactory->createServerRequest('GET', '/nonexistent');
            $instance->process($request, $requestHandler);
        })();
    }
}
