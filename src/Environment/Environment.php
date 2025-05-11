<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Environment;

class Environment implements EnvironmentInterface
{
    /** @param array<string, string> $env */
    public function __construct(private array $env)
    {
    }

    #[\Override]
    public function get(string $key, ?string $default = null): ?string
    {
        return $this->env[$key] ?? $default;
    }
}
