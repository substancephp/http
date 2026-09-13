<?php

namespace SubstancePHP\HTTP\Middleware;

use SubstancePHP\HTTP\ConfigurableMiddlewareInterface;

/** @template TConfig */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::IS_REPEATABLE)]
readonly class Configure
{
    /**
     * @param class-string<ConfigurableMiddlewareInterface<TConfig>> $middleware the PSR-15 middleware
     *   class to configure; it must implement {@see ConfigurableMiddlewareInterface}. Apply the attribute
     *   once per middleware; repeat it to configure several.
     * @param array<string, mixed> $config the configuration passed to the middleware's
     *   {@see ConfigurableMiddlewareInterface::parseConfig()}.
     */
    public function __construct(
        public string $middleware,
        public array $config,
    ) {
    }
}
