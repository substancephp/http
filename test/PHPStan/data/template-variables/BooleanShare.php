<?php

declare(strict_types=1);

namespace Test\PHPStan\Data\TemplateVariables;

use SubstancePHP\HTTP\ShareInterface;

/** A share publishing a boolean, to check that a narrowed tail does not change a template's contract. */
final readonly class BooleanShare implements ShareInterface
{
    public function __construct(public bool $emit = false)
    {
    }
}
