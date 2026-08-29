<?php

declare(strict_types=1);

namespace Test\Exception\RenderingException;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\RenderingException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingLayoutException;

#[CoversClass(MissingLayoutException::class)]
#[CoversMethod(MissingLayoutException::class, '__construct')]
#[CoversMethod(MissingLayoutException::class, 'getPath')]
class MissingLayoutExceptionTest extends TestCase
{
    #[Test]
    public function isARenderingException(): void
    {
        $exception = new MissingLayoutException('layouts/nope.html.php');
        $this->assertInstanceOf(RenderingException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame(
            'Layout template not found: layouts/nope.html.php',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function exposesThePath(): void
    {
        $exception = new MissingLayoutException('layouts/nope.html.php');
        $this->assertSame('layouts/nope.html.php', $exception->getPath());
    }
}
