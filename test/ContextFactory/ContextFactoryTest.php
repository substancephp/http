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
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\RequestParams\ServerParams;
use SubstancePHP\HTTP\Respond;

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
        $this->assertInstanceOf(Respond::class, $context->get(Respond::class));

        $this->assertSame('val', $context->get(ServerParams::class)['var']);
        $this->assertSame('there', $context->get(BodyParams::class)['hi']);
        $this->assertSame('bar', $context->get(QueryParams::class)['foo']);

        $this->assertSame('application/json', $context->get('substance.http.default-content-type'));
    }
}
