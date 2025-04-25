<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\RequestParams;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\RequestParams;

class ServerParams extends RequestParams
{
    public static function fromRequest(ServerRequestInterface $request): self
    {
        return new self($request->getServerParams());
    }
}
