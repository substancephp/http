<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Respond;

#[CoversClass(Respond::class)]
#[CoversMethod(Respond::class, '__construct')]
#[CoversMethod(Respond::class, '__invoke')]
#[CoversMethod(Respond::class, 'getStatusCode')]
#[CoversMethod(Respond::class, 'setHeader')]
#[CoversMethod(Respond::class, 'removeHeader')]
#[CoversMethod(Respond::class, 'getHeaderLine')]
#[CoversMethod(Respond::class, 'getHeaders')]
#[CoversMethod(Respond::class, 'setTemplate')]
#[CoversMethod(Respond::class, 'getTemplate')]
#[CoversMethod(Respond::class, 'removeTemplate')]
#[CoversMethod(Respond::class, 'redirectTo')]
class RespondTest extends TestCase
{
    #[Test]
    public function constructInvoke(): void
    {
        $instance = new Respond(200);
        $this->assertSame(200, $instance->getStatusCode());

        $result = $instance(422, ['message' => 'Invalid']);
        $this->assertSame(422, $instance->getStatusCode());
        $this->assertSame(['message' => 'Invalid'], $result);

        $result = $instance(401);
        $this->assertSame(401, $instance->getStatusCode());
        // assertSame, not assertNull: the no-data call is statically null.
        $this->assertSame(null, $result);
    }

    #[Test]
    public function setHeaderReplacesCaseInsensitively(): void
    {
        $instance = new Respond(200);
        $instance->setHeader('X-Foo', 'one');
        $this->assertSame('one', $instance->getHeaderLine('x-foo'));

        $instance->setHeader('X-Foo', ['three', 'four']);
        // The original spelling of the name is retained.
        $this->assertSame(['X-Foo' => ['three', 'four']], $instance->getHeaders());
    }

    #[Test]
    public function setHeaderSupportsMultipleValues(): void
    {
        $instance = new Respond(200);
        $instance->setHeader('Vary', ['Accept-Encoding', 'User-Agent']);
        $this->assertSame(['Vary' => ['Accept-Encoding', 'User-Agent']], $instance->getHeaders());
        $this->assertSame('Accept-Encoding, User-Agent', $instance->getHeaderLine('vary'));
    }

    #[Test]
    public function removeHeader(): void
    {
        $instance = new Respond(200);
        $instance->setHeader('Content-Type', 'text/html');
        $instance->removeHeader('CONTENT-TYPE');

        $this->assertSame([], $instance->getHeaders());
        $this->assertSame('', $instance->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function template(): void
    {
        $instance = new Respond(200);
        $this->assertNull($instance->getTemplate());

        $instance->setTemplate('stores/detail');
        $this->assertSame('stores/detail', $instance->getTemplate());

        $instance->removeTemplate();
        $this->assertNull($instance->getTemplate());
    }

    #[Test]
    public function redirectTo(): void
    {
        $instance = new Respond(200);
        $instance->setHeader('Content-Type', 'text/html');
        $instance->redirectTo('/foo');

        $this->assertSame(303, $instance->getStatusCode());
        // The content type is removed (so no body is rendered); the Location header is the only one left.
        $this->assertSame(['Location' => ['/foo']], $instance->getHeaders());

        $instance->setHeader('Content-Type', 'text/html');
        $instance->redirectTo('/bar', 301);
        $this->assertSame(301, $instance->getStatusCode());
        $this->assertSame('/bar', $instance->getHeaderLine('Location'));
    }
}
