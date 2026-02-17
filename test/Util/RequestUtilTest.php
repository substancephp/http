<?php

declare(strict_types=1);

namespace Test\Util;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Util\Request;

#[CoversClass(Request::class)]
#[CoversMethod(Request::class, 'containsJson')]
class RequestUtilTest extends TestCase
{
    #[Test]
    public function containsJson(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('POST', '/');
        $this->assertFalse(Request::containsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json');
        $this->assertTrue(Request::containsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('content-type', 'application/json');
        $this->assertTrue(Request::containsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('CONTENT-TYPE', 'application/json;charset=utf-8');
        $this->assertTrue(Request::containsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'text/html');
        $this->assertFalse(Request::containsJson($request));
    }
}
