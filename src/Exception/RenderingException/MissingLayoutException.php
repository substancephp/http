<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\RenderingException;

use SubstancePHP\HTTP\Exception\RenderingException;

/** Thrown when a layout template file does not exist. */
class MissingLayoutException extends RenderingException
{
    private readonly string $path;

    public function __construct(string $path)
    {
        parent::__construct("Layout template not found: {$path}");
        $this->path = $path;
    }

    /** The path of the layout file that was looked for. */
    public function getPath(): string
    {
        return $this->path;
    }
}
