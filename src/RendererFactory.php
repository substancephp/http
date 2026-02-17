<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use SubstancePHP\HTTP\Renderer\EmptyRenderer;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use SubstancePHP\HTTP\Renderer\JsonRenderer;

class RendererFactory implements RendererFactoryInterface
{
    public function __construct(
        private string $templateRoot,
    ) {
    }

    #[\Override]
    public function createRenderer(
        string $requestPath,
        string $responseContentType,
        mixed $responseData,
    ): RendererInterface {
        if (\str_starts_with($responseContentType, 'application/json')) {
            return new JsonRenderer($responseData);
        }
        if (\str_starts_with($responseContentType, 'text/html')) {
            $templatePath = "{$this->templateRoot}{$requestPath}.html.php";
            return new HtmlRenderer($templatePath, $responseData);
        }
        return new EmptyRenderer();
    }
}
