<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

readonly class ErrorResponseFallbackGenerator implements ErrorResponseFallbackGeneratorInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory, private ?LoggerInterface $logger)
    {
    }

    public function __invoke(\Throwable $e): ResponseInterface
    {
        $this->logger?->error(\get_class($e) . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
        return $this->responseFactory->createResponse(500)->withHeader('Content-Type', 'text/plain');
    }
}
