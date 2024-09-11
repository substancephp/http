<?php

namespace Test\RequestParams;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\RequestParams\ServerParams;

#[CoversClass(ServerParams::class)]
#[CoversMethod(ServerParams::class, 'from')]
class ServerParamsTest extends TestCase
{
    #[Test]
    public function from(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('GET', '/');
        $serverParams = ServerParams::from($request);
        $this->assertInstanceOf(ServerParams::class, $serverParams);
        $this->assertCount(0, $serverParams);

        $request = $requestFactory->createServerRequest('GET', '/', ['hello' => [10, 20, 30]]);
        $serverParams = ServerParams::from($request);
        $this->assertSame([10, 20, 30], $serverParams['hello']);
    }
}
