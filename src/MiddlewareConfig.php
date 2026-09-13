<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/** Reads the typed per-route configuration a {@see ConfigurableMiddlewareInterface} produced. */
final class MiddlewareConfig
{
    /**
     * The configuration parsed for the given middleware for the current request. Call this only from
     * within that middleware's own {@see MiddlewareInterface::process()}.
     *
     * @template TConfig
     * @param class-string<ConfigurableMiddlewareInterface<TConfig>> $middleware
     * @return TConfig
     */
    public static function for(ServerRequestInterface $request, string $middleware): mixed
    {
        /** @var TConfig */
        return $request->getAttribute($middleware);
    }
}
