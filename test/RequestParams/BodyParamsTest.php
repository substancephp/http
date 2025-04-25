<?php

namespace Test\RequestParams;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\RequestParams\BodyParams;

#[CoversClass(BodyParams::class)]
#[CoversMethod(BodyParams::class, 'fromRequest')]
class BodyParamsTest extends TestCase
{
    #[Test]
    public function fromRequest(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('GET', '/');
        $bodyParams = BodyParams::fromRequest($request);
        $this->assertSame(0, \count($bodyParams));
        $this->assertSame([], (array) $bodyParams);

        $request = $requestFactory->createServerRequest('GET', '/')->withParsedBody(['hello' => 'there']);
        $bodyParams = BodyParams::fromRequest($request);
        $this->assertSame('there', $bodyParams['hello']);
        $this->assertTrue($bodyParams->offsetExists('hello'));

        $request = $requestFactory->createServerRequest('GET', '/')->withParsedBody((object) ['hello' => [1, 2, 30]]);
        $bodyParams = BodyParams::fromRequest($request);
        $this->assertSame([1, 2, 30], $bodyParams['hello']);
    }
}
