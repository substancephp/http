<?php

namespace SubstancePHP\HTTP\Middleware;

use SubstancePHP\HTTP\MiddlewareSpec;
use SubstancePHP\HTTP\RequestHandler;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::IS_REPEATABLE)]
readonly class Engage
{
    /**
     * @param string $middleware the fully qualified class name of the PSR-15 middleware class that
     *   should run when handling a request-handling action callback via {@see RequestHandler}, even if
     *   it is disabled by default (see {@see MiddlewareSpec::disable()}). Apply the
     *   attribute once per middleware; repeat it to engage several.
     */
    public function __construct(public string $middleware)
    {
    }
}
