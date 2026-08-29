<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\RenderingException;

use SubstancePHP\HTTP\Exception\RenderingException;

/** Thrown when a partial template file does not exist. */
class MissingPartialException extends RenderingException
{
    private readonly string $path;

    public function __construct(string $path)
    {
        parent::__construct("Partial template not found: {$path}");
        $this->path = $path;
    }

    /** The path of the partial file that was looked for. */
    public function getPath(): string
    {
        return $this->path;
    }
}
