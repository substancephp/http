<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

interface RendererFactoryInterface
{
    public function createRenderer(
        string $requestPath,
        string $responseContentType,
        mixed $responseData,
    ): RendererInterface;
}
