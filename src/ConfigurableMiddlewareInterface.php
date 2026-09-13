<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Server\MiddlewareInterface;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;
use SubstancePHP\HTTP\Middleware\Configure;

/**
 * A PSR-15 middleware that accepts per-route configuration, declared on a route's action callback
 * with the {@see Configure} attribute and read back with {@see MiddlewareConfig::for()}.
 *
 * @template TConfig
 */
interface ConfigurableMiddlewareInterface extends MiddlewareInterface
{
    /**
     * Validate the raw per-route config and produce the typed config the middleware uses.
     *
     * The framework calls this once per request, before the middleware runs, and makes the result
     * available through {@see MiddlewareConfig::for()}. When a route does not configure the middleware
     * it is called with an empty array, so it must return the middleware's defaults.
     *
     * @param array<string, mixed> $config raw config as declared via {@see Configure}
     * @return TConfig the validated configuration
     * @throws InvalidMiddlewareException if the config is invalid
     */
    public function parseConfig(array $config): mixed;
}
