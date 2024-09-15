<?php

namespace Test\RequestParams;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\RequestParams\ServerParams;

#[CoversClass(ServerParams::class)]
#[CoversMethod(ServerParams::class, '__construct')]
class ServerParamsTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('GET', '/');
        $serverParams = new ServerParams($request);
        $this->assertInstanceOf(ServerParams::class, $serverParams);
        $this->assertCount(0, $serverParams);

        $request = $requestFactory->createServerRequest('GET', '/', ['hello' => [10, 20, 30]]);
        $serverParams = new ServerParams($request);
        $this->assertSame([10, 20, 30], $serverParams['hello']);
    }
}
