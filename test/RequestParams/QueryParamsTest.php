<?php

namespace Test\RequestParams;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\RequestParams\QueryParams;

#[CoversClass(QueryParams::class)]
#[CoversMethod(QueryParams::class, '__construct')]
#[CoversMethod(QueryParams::class, '__toString')]
class QueryParamsTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('GET', '/');
        $queryParams = new QueryParams($request);
        $this->assertInstanceOf(QueryParams::class, $queryParams);
        $this->assertSame(0, \count($queryParams));
        $this->assertSame([], (array) $queryParams);

        $request = $requestFactory->createServerRequest('GET', '/')->withQueryParams(['hello' => 'there']);
        $queryParams = new QueryParams($request);
        $this->assertSame('there', $queryParams['hello']);
        $this->assertTrue($queryParams->offsetExists('hello'));

        $request = $requestFactory->createServerRequest('GET', '/')->withQueryParams(['hello' => [1, 2, 30]]);
        $queryParams = new QueryParams($request);
        $this->assertSame([1, 2, 30], $queryParams['hello']);
    }

    #[Test]
    public function castToString(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('GET', '/')->withQueryParams(['hello' => 'there']);
        $queryParams = new QueryParams($request);
        $this->assertSame('hello=there', (string) $queryParams);
    }
}
