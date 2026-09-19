<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * Declares templates an action may render in addition to its default one, so that a choice made at runtime
 * from a fixed set is visible to static analysis.
 *
 * The templates are named relative to the template root, without the `.html.php` suffix, exactly as
 * {@see Respond::setTemplate()} names them.
 *
 * Apply it to the callback the action file returns.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
final readonly class AltTemplates
{
    /** @param string[] $templates template paths relative to the template root, without the suffix */
    public function __construct(public array $templates)
    {
    }
}
