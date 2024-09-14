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
#[CoversMethod(Request::class, 'acceptsJson')]
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

    #[Test]
    public function acceptsJson(): void
    {
        $requestFactory = new ServerRequestFactory();

        $request = $requestFactory->createServerRequest('POST', '/');
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Accept', 'application/json');
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('ACCEPT', 'application/json');
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('accept', 'application/json;charset=utf-8');
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('accept', 'application/json;charset=utf-32');
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Accept', ['application/json;charset=utf-32', 'text/plain;charset=utf-8']);
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Accept', ['text/plain', 'application/json']);
        $this->assertTrue(Request::acceptsJson($request));

        $request = $requestFactory->createServerRequest('POST', '/')
            ->withHeader('Accept', 'text/html');
        $this->assertFalse(Request::acceptsJson($request));
    }
}
