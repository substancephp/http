<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\BaseException;

use SubstancePHP\HTTP\Exception\BaseException;

/** Thrown when a middleware stack, or a route's middleware declarations, are inconsistent or invalid. */
class InvalidMiddlewareException extends BaseException
{
}
