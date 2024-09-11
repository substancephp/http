<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\RequestParams;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\RequestParams;

class BodyParams extends RequestParams
{
    public static function from(ServerRequestInterface $request): self
    {
        $parsedBody = $request->getParsedBody();

        if ($parsedBody === null) {
            return new self([]);
        }
        if (\is_array($parsedBody)) {
            return new self($parsedBody);
        }
        return new self((array) $parsedBody);
    }
}
