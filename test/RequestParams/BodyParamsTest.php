<?php

namespace Test\RequestParams;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\RequestParams\BodyParams;

#[CoversClass(BodyParams::class)]
#[CoversMethod(BodyParams::class, '__construct')]
class BodyParamsTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('GET', '/');
        $bodyParams = new BodyParams($request);
        $this->assertInstanceOf(BodyParams::class, $bodyParams);
        $this->assertSame(0, \count($bodyParams));
        $this->assertSame([], (array) $bodyParams);

        $request = $requestFactory->createServerRequest('GET', '/')->withParsedBody(['hello' => 'there']);
        $bodyParams = new BodyParams($request);
        $this->assertSame('there', $bodyParams['hello']);
        $this->assertTrue($bodyParams->offsetExists('hello'));

        $request = $requestFactory->createServerRequest('GET', '/')->withParsedBody((object) ['hello' => [1, 2, 30]]);
        $bodyParams = new BodyParams($request);
        $this->assertSame([1, 2, 30], $bodyParams['hello']);
    }
}
