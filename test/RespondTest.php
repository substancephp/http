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
class RespondTest extends TestCase
{
    #[Test]
    public function constructInvoke(): void
    {
        $instance = new Respond(200, 'text/html');
        $this->assertSame(200, $instance->statusCode);

        $result = $instance(422, ['message' => 'Invalid']);
        $this->assertSame(422, $instance->statusCode);
        $this->assertSame(['message' => 'Invalid'], $result);
        $this->assertSame('text/html', $instance->contentType);

        $result = $instance(401, ['message' => 'Wrongo'], 'application/json');
        $this->assertSame(401, $instance->statusCode);
        $this->assertSame(['message' => 'Wrongo'], $result);
        $this->assertSame('application/json', $instance->contentType);
    }
}
