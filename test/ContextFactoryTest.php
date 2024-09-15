<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\ContextFactory;
use SubstancePHP\HTTP\RequestParams\BodyParams;
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\RequestParams\ServerParams;

#[CoversClass(ContextFactory::class)]
#[CoversMethod(ContextFactory::class, 'createContext')]
class ContextFactoryTest extends TestCase
{
    #[Test]
    public function createContext(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('get')->with('something')->willReturn('dummy-value');
        $container->expects($this->any())->method('has')->with('something')->willReturn(true);
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', 'http://example.com/', ['var' => 'val'])
            ->withParsedBody(['hi' => 'there'])
            ->withQueryParams(['foo' => 'bar']);

        $contextFactory = new ContextFactory();

        $context = $contextFactory->createContext($container, $request);
        $this->assertInstanceOf(Container::class, $context);

        $this->assertInstanceOf(QueryParams::class, $context->get(QueryParams::class));
        $this->assertInstanceOf(ServerParams::class, $context->get(ServerParams::class));
        $this->assertInstanceOf(BodyParams::class, $context->get(BodyParams::class));

        $this->assertSame('val', $context->get(ServerParams::class)['var']);
        $this->assertSame('there', $context->get(BodyParams::class)['hi']);
        $this->assertSame('bar', $context->get(QueryParams::class)['foo']);

        $this->assertSame('dummy-value', $context->get('something'));
    }
}
