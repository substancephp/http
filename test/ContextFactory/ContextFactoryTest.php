<?php

declare(strict_types=1);

namespace Test\ContextFactory;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactory;
use SubstancePHP\HTTP\RequestParams\BodyParams;
use SubstancePHP\HTTP\RequestParams\PathParams;
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\RequestParams\ServerParams;
use SubstancePHP\HTTP\Respond;
use SubstancePHP\HTTP\Route;
use TestUtil\TestUtil;

#[CoversClass(ContextFactory::class)]
#[CoversMethod(ContextFactory::class, 'createContext')]
#[AllowMockObjectsWithoutExpectations]
class ContextFactoryTest extends TestCase
{
    #[Test]
    public function createContext(): void
    {
        $container = Container::from([
            'substance.http.default-content-type' => fn () => 'application/json',
        ]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'http://example.com/', ['var' => 'val'])
            ->withParsedBody(['hi' => 'there'])
            ->withQueryParams(['foo' => 'bar']);

        $contextFactory = new ContextFactory();

        $context = $contextFactory->createContext($container, $request);

        $this->assertInstanceOf(QueryParams::class, $context->get(QueryParams::class));
        $this->assertInstanceOf(ServerParams::class, $context->get(ServerParams::class));
        $this->assertInstanceOf(BodyParams::class, $context->get(BodyParams::class));
        $this->assertInstanceOf(PathParams::class, $context->get(PathParams::class));
        $this->assertInstanceOf(Respond::class, $context->get(Respond::class));

        $this->assertSame('val', $context->get(ServerParams::class)['var']);
        $this->assertSame('there', $context->get(BodyParams::class)['hi']);
        $this->assertSame('bar', $context->get(QueryParams::class)['foo']);
        $this->assertSame([], (array) $context->get(PathParams::class));

        $this->assertSame('application/json', $context->get('substance.http.default-content-type'));
    }

    #[Test]
    public function createContextPopulatesPathParamsFromRoute(): void
    {
        $container = Container::from(['substance.http.default-content-type' => fn () => 'application/json']);

        $requestFactory = new ServerRequestFactory();
        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/stores/42');
        \assert($route instanceof Route);

        $request = $requestFactory->createServerRequest('GET', '/stores/42')->withAttribute(Route::class, $route);

        $context = (new ContextFactory())->createContext($container, $request);

        $this->assertSame(['id' => '42'], (array) $context->get(PathParams::class));
        $this->assertSame('42', $context->get(PathParams::class)['id']);
    }
}
