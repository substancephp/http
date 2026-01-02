<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use SubstancePHP\HTTP\ErrorResponseFallbackGenerator;

#[CoversClass(ErrorResponseFallbackGenerator::class)]
#[CoversMethod(ErrorResponseFallbackGenerator::class, '__invoke')]
#[AllowMockObjectsWithoutExpectations]
class ErrorResponseFallbackGeneratorTest extends TestCase
{
    #[Test]
    public function invokeWhenLoggerPresent(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->method('createResponse')->willReturn(new Response(status: 418));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $instance = new ErrorResponseFallbackGenerator($responseFactory, $logger);
        try {
            throw new \LogicException('hi');
        } catch (\LogicException $e) {
            $response = $instance->__invoke($e);
            $this->assertSame(418, $response->getStatusCode());
        }
    }

    #[Test]
    public function invokeWhenLoggerAbsent(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->method('createResponse')->willReturn(new Response(status: 418));

        $logger = null;

        $instance = new ErrorResponseFallbackGenerator($responseFactory, $logger);
        try {
            throw new \LogicException('hi');
        } catch (\LogicException $e) {
            $response = $instance->__invoke($e);
            $this->assertSame(418, $response->getStatusCode());
        }
    }
}
