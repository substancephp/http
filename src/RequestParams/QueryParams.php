<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\RequestParams;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\RequestParams;

class QueryParams extends RequestParams
{
    public static function from(ServerRequestInterface $request): self
    {
        return new self($request->getQueryParams());
    }

    public function __toString(): string
    {
        return \http_build_query((array) $this);
    }
}
