<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\Escaper\Escaper;
use SubstancePHP\HTTP\Exception\RenderingException\UnsupportedContentTypeException;
use SubstancePHP\HTTP\Renderer\HtmlRenderer;
use SubstancePHP\HTTP\Renderer\JsonRenderer;

class RendererFactory implements RendererFactoryInterface
{
    public function __construct(private Templating $templating)
    {
    }

    #[\Override]
    public function createRenderer(RenderInput $input): RendererInterface
    {
        if (\str_starts_with($input->contentType, 'application/json')) {
            return new JsonRenderer($input->data);
        }
        if (\str_starts_with($input->contentType, 'text/html')) {
            // The shared template variables are resolved here, lazily: only an HTML renderer uses them, so
            // other content types (e.g. JSON) never trigger the factory.
            $share = ($input->share instanceof \Closure) ? ($input->share)() : $input->share;
            return new HtmlRenderer(
                templatePath: "{$this->templating->root}/{$input->path}.html.php",
                data: $input->data,
                escaper: new Escaper($this->templating->encoding),
                templateRoot: $this->templating->root,
                share: $share,
                assets: $this->templating->assets,
                defaultLayout: $this->templating->defaultLayout,
            );
        }
        throw new UnsupportedContentTypeException($input->contentType);
    }
}
