<?php

namespace SubstancePHP\HTTP\Middleware;

use SubstancePHP\HTTP\RequestHandler;

#[\Attribute(\Attribute::TARGET_FUNCTION)]
readonly class Engage
{
    /** @var string[] */
    public array $engagedMiddlewares;

    /**
     * @param string ...$middlewares the fully qualified class names of the PSR-15 middleware classes that
     *   should run when handling a request-handling action callback via {@see RequestHandler}, even if
     *   they are disabled by default (see {@see \SubstancePHP\HTTP\MiddlewareSpec::disable()}).
     */
    public function __construct(string ...$middlewares)
    {
        $this->engagedMiddlewares = $middlewares;
    }
}
