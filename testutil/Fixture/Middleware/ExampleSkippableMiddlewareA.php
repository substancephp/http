<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Middleware\Base\SkippableMiddleware;

readonly class ExampleSkippableMiddlewareA extends SkippableMiddleware
{
    /**
     * @inheritDoc
     */
    protected function doProcess(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return $handler->handle($request->withAttribute('example skippable middleware A called', true));
    }
}
