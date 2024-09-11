<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Util;

use Psr\Http\Message\ServerRequestInterface;

final class RequestUtil
{
    public static function containsJson(ServerRequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');
        return \strlen($contentType) != 0 && \str_contains($contentType, 'application/json');
    }

    public static function acceptsJson(ServerRequestInterface $request): bool
    {
        $accepts = $request->getHeaderLine('Accept');
        return \strlen($accepts) == 0 || \str_contains($accepts, 'application/json');
    }
}
