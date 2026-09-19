<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * The default {@see ShareInterface}: contributes no variables, so an application that has no shared template
 * data still renders. It is also what an error page falls back to when the application's share cannot be
 * resolved.
 */
final class EmptyShare implements ShareInterface
{
}
