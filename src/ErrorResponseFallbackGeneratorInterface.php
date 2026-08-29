<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseInterface;

interface ErrorResponseFallbackGeneratorInterface
{
    /** @param ?string $correlationId if given, included in the log line; null leaves it unchanged. */
    public function __invoke(\Throwable $e, ?string $correlationId = null): ResponseInterface;
}
