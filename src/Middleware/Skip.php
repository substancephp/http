<?php

namespace SubstancePHP\HTTP\Middleware;

use SubstancePHP\HTTP\RequestHandler;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::IS_REPEATABLE)]
readonly class Skip
{
    /**
     * @param string $middleware the fully qualified class name of the PSR-15 middleware class that
     *   should be skipped when running a request-handling action callback via {@see RequestHandler}.
     *   Apply the attribute once per middleware; repeat it to skip several.
     */
    public function __construct(public string $middleware)
    {
    }
}
