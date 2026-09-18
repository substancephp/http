<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

interface RendererFactoryInterface
{
    public function createRenderer(RenderInput $input): RendererInterface;
}
