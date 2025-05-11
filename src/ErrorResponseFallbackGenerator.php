<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

readonly class ErrorResponseFallbackGenerator
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(\Throwable $e): ResponseInterface
    {
        $message = \get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
        foreach ($e->getTrace() as $trace) {
            $message .= ($trace['file'] ?? '') . ':' . ($trace['line'] ?? '') . PHP_EOL;
        }
        $message .= $e->getTraceAsString();
        $this->logger->error($message);
        return $this->responseFactory->createResponse(500)->withHeader('Content-Type', 'text/plain');
    }
}
