<?php

declare(strict_types=1);

namespace Test\PHPStan\Data\TemplateVariables;

use SubstancePHP\HTTP\ShareInterface;

/** The application's share, for exercising the exemption of shared variables. */
final readonly class AppShared implements ShareInterface
{
    public function __construct(public string $appName)
    {
    }
}
