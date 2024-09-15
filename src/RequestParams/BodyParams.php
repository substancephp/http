<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\RequestParams;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\RequestParams;

class BodyParams extends RequestParams
{
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct((array) ($request->getParsedBody() ?? []));
    }
}
