<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

interface EnvironmentInterface
{
    public function get(string $key, ?string $default = null): ?string;
}
