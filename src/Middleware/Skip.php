<?php

namespace SubstancePHP\HTTP\Middleware;

#[\Attribute(\Attribute::TARGET_FUNCTION)]
readonly class Skip
{
    /** @var string[] */
    public array $skippableMiddlewares;

    /**
     * @param string ...$middlewares the names of the PSR-15 middleware classes that should be skipped
     *   when running a request-handling action callback.
     */
    public function __construct(string ...$middlewares)
    {
        $this->skippableMiddlewares = $middlewares;
    }
}
