<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Util;

use Psr\Http\Message\ServerRequestInterface;

final class Request
{
    public static function containsJson(ServerRequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');
        return \strlen($contentType) != 0 && \str_contains($contentType, 'application/json');
    }
}
