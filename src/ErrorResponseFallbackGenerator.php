<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

readonly class ErrorResponseFallbackGenerator implements ErrorResponseFallbackGeneratorInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ?LoggerInterface $logger,
    ) {
    }

    /** @param ?string $correlationId if given, included in the log line; null leaves it unchanged. */
    public function __invoke(\Throwable $e, ?string $correlationId = null): ResponseInterface
    {
        $message = \get_class($e) . ': ' . $e->getMessage();
        if ($correlationId !== null) {
            $message = "[$correlationId] $message";
        }
        $this->logger?->error($message . PHP_EOL . $e->getTraceAsString());
        return $this->responseFactory->createResponse(500)->withHeader('Content-Type', 'text/plain');
    }
}
