<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Describes one middleware in an application's middleware stack. In particular it records whether the
 * middleware is enabled by default.
 *
 * A bare class name passed to {@see Application::make()} is equivalent to {@see self::enable()}.
 * {@see self::disable()} marks a middleware as skipped unless a route opts it back in with
 * {@see \SubstancePHP\HTTP\Middleware\Engage}.
 */
final readonly class MiddlewareSpec
{
    /** @param class-string<MiddlewareInterface> $class */
    private function __construct(
        public string $class,
        public bool $enabledByDefault,
    ) {
    }

    /** @param class-string<MiddlewareInterface> $class */
    public static function enable(string $class): self
    {
        return new self($class, true);
    }

    /** @param class-string<MiddlewareInterface> $class */
    public static function disable(string $class): self
    {
        return new self($class, false);
    }
}
