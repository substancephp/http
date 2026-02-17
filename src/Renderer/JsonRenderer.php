<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Renderer;

use SubstancePHP\HTTP\RendererInterface;

class JsonRenderer implements RendererInterface
{
    public function __construct(
        private mixed $data,
    ) {
    }

    /** @throws \JsonException */
    #[\Override]
    public function render(): string
    {
        return \json_encode($this->data, \JSON_THROW_ON_ERROR);
    }
}
