<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Environment;

interface EnvironmentInterface
{
    public function get(string $key, ?string $default = null): ?string;
}
