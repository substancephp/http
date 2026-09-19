<?php

declare(strict_types=1);

namespace Test\Exception\RenderingException;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Exception\RenderingException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingAssetException;

#[CoversClass(MissingAssetException::class)]
#[CoversMethod(MissingAssetException::class, '__construct')]
#[CoversMethod(MissingAssetException::class, 'getPath')]
class MissingAssetExceptionTest extends TestCase
{
    #[Test]
    public function isARenderingException(): void
    {
        $exception = new MissingAssetException('css/nope.css');
        $this->assertInstanceOf(RenderingException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('Asset not found: css/nope.css', $exception->getMessage());
    }

    #[Test]
    public function exposesThePath(): void
    {
        $exception = new MissingAssetException('css/nope.css');
        $this->assertSame('css/nope.css', $exception->getPath());
    }
}
