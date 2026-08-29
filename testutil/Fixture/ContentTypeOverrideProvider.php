<?php

declare(strict_types=1);

namespace TestUtil\Fixture;

use SubstancePHP\HTTP\EnvironmentInterface;
use SubstancePHP\HTTP\ProviderInterface;

/** Overrides `substance.http.default-content-type`, proving app-level overrides beat the default. */
class ContentTypeOverrideProvider implements ProviderInterface
{
    /** @inheritDoc */
    #[\Override]
    public static function factories(EnvironmentInterface $environment): array
    {
        return [
            'substance.http.default-content-type' => fn () => 'application/json',
        ];
    }
}
