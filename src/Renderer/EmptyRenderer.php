<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Renderer;

use SubstancePHP\HTTP\RendererInterface;

class EmptyRenderer implements RendererInterface
{
    #[\Override]
    public function render(): string
    {
        return '';
    }
}
