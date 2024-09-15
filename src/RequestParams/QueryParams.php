<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\RequestParams;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\RequestParams;

class QueryParams extends RequestParams
{
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request->getQueryParams());
    }

    public function __toString(): string
    {
        return \http_build_query((array) $this);
    }
}
