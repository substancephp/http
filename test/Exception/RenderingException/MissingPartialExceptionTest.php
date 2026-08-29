<?php

declare(strict_types=1);

namespace Test\Exception\RenderingException;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\RenderingException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingPartialException;

#[CoversClass(MissingPartialException::class)]
#[CoversMethod(MissingPartialException::class, '__construct')]
#[CoversMethod(MissingPartialException::class, 'getPath')]
class MissingPartialExceptionTest extends TestCase
{
    #[Test]
    public function isARenderingException(): void
    {
        $exception = new MissingPartialException('partials/nope.html.php');
        $this->assertInstanceOf(RenderingException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame(
            'Partial template not found: partials/nope.html.php',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function exposesThePath(): void
    {
        $exception = new MissingPartialException('partials/nope.html.php');
        $this->assertSame('partials/nope.html.php', $exception->getPath());
    }
}
