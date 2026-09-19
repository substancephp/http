<?php

declare(strict_types=1);

namespace Test\PHPStan\Data\TemplateVariables;

use SubstancePHP\HTTP\ShareInterface;

/** A share whose variable is nullable, which a template declaring it non-nullable would not accept. */
final readonly class NullableShare implements ShareInterface
{
    public function __construct(public ?string $appName = null)
    {
    }
}
