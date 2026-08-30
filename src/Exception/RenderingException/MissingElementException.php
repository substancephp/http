<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\RenderingException;

use SubstancePHP\HTTP\Exception\RenderingException;

/** Thrown when an element template file does not exist. */
class MissingElementException extends RenderingException
{
    private readonly string $path;

    public function __construct(string $path)
    {
        parent::__construct("Element template not found: {$path}");
        $this->path = $path;
    }

    /** The path of the element template that was looked for. */
    public function getPath(): string
    {
        return $this->path;
    }
}
