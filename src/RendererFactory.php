<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\Escaper\Escaper;
use SubstancePHP\HTTP\Renderer\EmptyRenderer;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use SubstancePHP\HTTP\Renderer\JsonRenderer;

class RendererFactory implements RendererFactoryInterface
{
    /** @param non-empty-string $htmlEncoding */
    public function __construct(
        private string $templateRoot,
        private string $htmlEncoding,
    ) {
    }

    #[\Override]
    public function createRenderer(
        string $normalizedRequestPath,
        string $responseContentType,
        mixed $responseData,
    ): RendererInterface {
        if (\str_starts_with($responseContentType, 'application/json')) {
            return new JsonRenderer($responseData);
        }
        if (\str_starts_with($responseContentType, 'text/html')) {
            $templatePath = "{$this->templateRoot}/{$normalizedRequestPath}.html.php";
            $escaper = new Escaper($this->htmlEncoding);
            return new HtmlRenderer($templatePath, $responseData, $escaper);
        }
        return new EmptyRenderer();
    }
}
