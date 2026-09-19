<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\RenderingException;

use SubstancePHP\HTTP\Exception\RenderingException;

/** Thrown when an asset file does not exist. */
class MissingAssetException extends RenderingException
{
    private readonly string $path;

    public function __construct(string $path)
    {
        parent::__construct("Asset not found: {$path}");
        $this->path = $path;
    }

    /** The path of the asset file that was looked for. */
    public function getPath(): string
    {
        return $this->path;
    }
}
