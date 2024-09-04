<?php

namespace SubstancePHP\HTTP\Middleware;

use SubstancePHP\HTTP\Middleware\Base\SkippableMiddleware;

#[\Attribute(\Attribute::TARGET_FUNCTION)]
readonly class Skip
{
    /** @var string[] */
    public array $skippableMiddlewares;

    /**
     * @param string ...$middlewares the names of the PSR-15 middleware classes that should be skipped
     *   when running a request-handling action callback. These should inherit from {@see SkippableMiddleware}
     *   for this attribute to take effect.
     */
    public function __construct(string ...$middlewares)
    {
        $this->skippableMiddlewares = $middlewares;
    }
}
