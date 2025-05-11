<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Provider;

use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Environment\EnvironmentInterface;

interface ProviderInterface
{
    /** @return array<string, \Closure(Container $c, string $id): mixed> */
    public static function factories(EnvironmentInterface $environment): array;
}
