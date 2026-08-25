<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\RequestParams;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\RequestParams;
use SubstancePHP\HTTP\Route;

class PathParams extends RequestParams
{
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $route = $request->getAttribute(Route::class);
        return new self($route instanceof Route ? $route->getParams() : []);
    }
}
