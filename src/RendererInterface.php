<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

interface RendererInterface
{
    public function render(): string;
}
