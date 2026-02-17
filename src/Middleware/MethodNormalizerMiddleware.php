<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodNormalizerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $overridingMethod = $request->getHeaderLine('X-Http-Method-Override');
        if (\strlen($overridingMethod) == 0) {
            $body = $request->getParsedBody();
            $overridingMethod = match (\is_array($body)) {
                true => $body['_METHOD'] ?? $body['_method'] ?? null,
                false => $body->_METHOD ?? $body->_method ?? null,
            };
        }
        return match ($overridingMethod) {
            null => $handler->handle($request),
            default => $handler->handle($request->withMethod($overridingMethod)),
        };
    }
}
