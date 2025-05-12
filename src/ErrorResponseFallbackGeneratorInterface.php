<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseInterface;

interface ErrorResponseFallbackGeneratorInterface
{
    public function __invoke(\Throwable $e): ResponseInterface;
}
