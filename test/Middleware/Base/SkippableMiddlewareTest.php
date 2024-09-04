<?php

declare(strict_types=1);

namespace Test\Middleware\Base;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\BaseException\UnexpectedRequestAttributeValueException;
use SubstancePHP\HTTP\Middleware\Base\SkippableMiddleware;
use SubstancePHP\HTTP\RequestHandler;
use SubstancePHP\HTTP\Route;
use TestUtil\Fixture\Middleware\AttributeGatheringMiddleware;
use TestUtil\Fixture\Middleware\ExampleNonSkippableMiddleware;
use TestUtil\Fixture\Middleware\ExampleSkippableMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleSkippableMiddlewareB;

#[CoversClass(SkippableMiddleware::class)]
#[CoversMethod(SkippableMiddleware::class, 'process')]
class SkippableMiddlewareTest extends TestCase
{
    #[Test]
    public function process(): void
    {
        // setup
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $makeRequestHandler = fn () => RequestHandler::from([
            new ExampleNonSkippableMiddleware(),
            new ExampleSkippableMiddlewareA(),
            new ExampleSkippableMiddlewareB(),
            new AttributeGatheringMiddleware($responseFactory),
        ]);
        $actionRoot = dirname(__DIR__, 3) . '/testutil/Fixture/action';
        $route = Route::from($actionRoot, 'GET', '/dummy');

        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, $route);

        // test happy
        $response = $makeRequestHandler()->handle($request);
        $requestAttributes = $response->getHeader('X-Request-Attributes');
        $this->assertCount(1, $requestAttributes);
        $expected = '{' .
            '"SubstancePHP\\\\HTTP\\\\Route":{},' .
            '"example non-skippable middleware called":true,' .
            '"example skippable middleware B called":true,' .
            '"attribute gathering middleware called":true' .
            '}';
        $this->assertSame($expected, $requestAttributes[0]);

        // test unhappy - bad route
        $request = $requestFactory->createServerRequest('GET', '/ignore')
            ->withAttribute(Route::class, null);
        $this->expectException(UnexpectedRequestAttributeValueException::class);
        $makeRequestHandler()->handle($request);
    }
}
