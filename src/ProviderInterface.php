<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use SubstancePHP\Container\Container;

interface ProviderInterface
{
    /** @return array<string, \Closure(Container $c, string $id): mixed> */
    public static function factories(EnvironmentInterface $environment): array;
}
