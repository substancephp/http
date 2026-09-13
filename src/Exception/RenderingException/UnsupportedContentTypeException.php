<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\RenderingException;

use SubstancePHP\HTTP\Exception\RenderingException;

/** Thrown when no renderer is registered for the response content type. */
class UnsupportedContentTypeException extends RenderingException
{
    private readonly string $contentType;

    public function __construct(string $contentType)
    {
        parent::__construct("No renderer for content type: {$contentType}");
        $this->contentType = $contentType;
    }

    /** The content type for which no renderer could be found. */
    public function getContentType(): string
    {
        return $this->contentType;
    }
}
