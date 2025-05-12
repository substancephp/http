<?php

namespace SubstancePHP\HTTP\Middleware;

use SubstancePHP\HTTP\RequestHandler;

#[\Attribute(\Attribute::TARGET_FUNCTION)]
readonly class Skip
{
    /** @var string[] */
    public array $skippableMiddlewares;

    /**
     * @param string ...$middlewares the fully qualified class names of the PSR-15 middleware classes that should be
     *   skipped when running a request-handling action callback via {@see \SubstancePHP\HTTP\RequestHandler}.
     */
    public function __construct(string ...$middlewares)
    {
        $this->skippableMiddlewares = $middlewares;
    }
}
