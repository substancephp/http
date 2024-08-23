<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SubstancePHP\HTTP\Out;
use SubstancePHP\HTTP\Status;

#[CoversClass(Out::class)]
#[CoversMethod(Out::class, '__construct')]
#[CoversMethod(Out::class, 'data')]
#[CoversMethod(Out::class, 'noContent')]
#[CoversMethod(Out::class, 'message')]
#[CoversMethod(Out::class, 'userError')]
#[CoversMethod(Out::class, 'notFound')]
#[CoversMethod(Out::class, 'unauthorized')]
#[CoversMethod(Out::class, 'getStatus')]
#[CoversMethod(Out::class, 'getData')]
#[CoversMethod(Out::class, 'getMessage')]
#[CoversMethod(Out::class, 'isSuccessful')]
#[CoversMethod(Out::class, 'getConsoleCode')]
#[CoversMethod(Out::class, 'getHTTPCode')]
class OutTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $out = new Out(Status::CREATED, ['name' => 'Stan'], 'Hi');
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(Status::CREATED, $out->getStatus());
        $this->assertSame(['name' => 'Stan'], $out->getData());
        $this->assertSame('Hi', $out->getMessage());

        $out = new Out(Status::CREATED, ['name' => 'Stan']);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(Status::CREATED, $out->getStatus());
        $this->assertSame(['name' => 'Stan'], $out->getData());
        $this->assertNull($out->getMessage());

        $out = new Out(Status::CREATED);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertNull($out->getData());

        $out = new Out();
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(Status::OK, $out->getStatus());
    }

    #[Test]
    public function data(): void
    {
        $out = Out::data(['name' => 'Stan'], Status::CREATED);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(['name' => 'Stan'], $out->getData());
        $this->assertSame(Status::CREATED, $out->getStatus());

        $out = Out::data(['name' => 'Stan']);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame(['name' => 'Stan'], $out->getData());
        $this->assertSame(Status::OK, $out->getStatus());
    }

    #[Test]
    public function noContent(): void
    {
        $out = Out::noContent();
        $this->assertInstanceOf(Out::class, $out);
        $this->assertNull($out->getData());
        $this->assertNull($out->getMessage());
    }

    #[Test]
    public function message(): void
    {
        $out = Out::message('Hello');
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame('Hello', $out->getMessage());

        $out = Out::message('Hello', Status::IM_A_TEAPOT);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame('Hello', $out->getMessage());
        $this->assertSame(Status::IM_A_TEAPOT, $out->getStatus());
    }

    #[Test]
    public function userError(): void
    {
        $out = Out::userError();
        $this->assertInstanceOf(Out::class, $out);
        $this->assertNull($out->getMessage());
        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $out->getStatus());

        $out = Out::userError('Hello');
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame('Hello', $out->getMessage());
        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $out->getStatus());

        $out = Out::userError('Hello', Status::UNAUTHORIZED);
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame('Hello', $out->getMessage());
        $this->assertSame(Status::UNAUTHORIZED, $out->getStatus());
    }

    #[Test]
    public function notFound(): void
    {
        $out = Out::notFound();
        $this->assertInstanceOf(Out::class, $out);
        $this->assertNull($out->getMessage());
        $this->assertSame(Status::NOT_FOUND, $out->getStatus());

        $out = Out::notFound('Hello');
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame('Hello', $out->getMessage());
        $this->assertSame(Status::NOT_FOUND, $out->getStatus());
    }

    #[Test]
    public function unauthorized(): void
    {
        $out = Out::unauthorized();
        $this->assertInstanceOf(Out::class, $out);
        $this->assertNull($out->getMessage());
        $this->assertSame(Status::UNAUTHORIZED, $out->getStatus());

        $out = Out::unauthorized('Hello');
        $this->assertInstanceOf(Out::class, $out);
        $this->assertSame('Hello', $out->getMessage());
        $this->assertSame(Status::UNAUTHORIZED, $out->getStatus());
    }

    #[Test]
    public function getStatus(): void
    {
        $out = new Out(Status::CREATED, ['name' => 'Stan'], 'Hi');
        $this->assertSame(Status::CREATED, $out->getStatus());
    }

    #[Test]
    public function getData(): void
    {
        $out = new Out(Status::CREATED, ['name' => 'Stan'], 'Hi');
        $this->assertSame(['name' => 'Stan'], $out->getData());
    }

    #[Test]
    public function getMessage(): void
    {
        $out = new Out(Status::CREATED, ['name' => 'Stan'], 'Hi');
        $this->assertSame('Hi', $out->getMessage());
    }

    #[Test]
    public function isSuccessful(): void
    {
        $out = new Out(Status::CREATED);
        $this->assertTrue($out->isSuccessful());

        $out = new Out(Status::UNAUTHORIZED);
        $this->assertFalse($out->isSuccessful());
    }

    #[Test]
    public function getConsoleCode(): void
    {
        $out = new Out(Status::CREATED);
        $this->assertEquals(0, $out->getConsoleCode());

        $out = new Out(Status::UNAUTHORIZED);
        $this->assertEquals(1, $out->getConsoleCode());
    }

    #[Test]
    public function getHTTPCode(): void
    {
        $out = new Out(Status::CREATED);
        $this->assertEquals(201, $out->getHTTPCode());

        $out = new Out(Status::UNAUTHORIZED);
        $this->assertEquals(401, $out->getHTTPCode());
    }
}
