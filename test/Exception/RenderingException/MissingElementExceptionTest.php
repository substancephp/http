<?php

declare(strict_types=1);

namespace Test\Exception\RenderingException;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\RenderingException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingElementException;

#[CoversClass(MissingElementException::class)]
#[CoversMethod(MissingElementException::class, '__construct')]
#[CoversMethod(MissingElementException::class, 'getPath')]
class MissingElementExceptionTest extends TestCase
{
    #[Test]
    public function isARenderingException(): void
    {
        $exception = new MissingElementException('elements/nope.html.php');
        $this->assertInstanceOf(RenderingException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame(
            'Element template not found: elements/nope.html.php',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function exposesThePath(): void
    {
        $exception = new MissingElementException('elements/nope.html.php');
        $this->assertSame('elements/nope.html.php', $exception->getPath());
    }
}
