<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Middleware;

final readonly class ConfigurableMiddlewareConfig
{
    public function __construct(public int $rate = 10)
    {
    }
}
