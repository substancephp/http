<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * Declares the template an action renders by default, in place of the one its route resolves to.
 *
 * The template is named relative to the template root, without the `.html.php` suffix, exactly as
 * {@see Respond::setTemplate()} names it. Without this attribute the route's own template is the default, so
 * an action that follows the convention declares nothing.
 *
 * Apply it to the callback the action file returns.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
final readonly class DefaultTemplate
{
    /** @param string $template a template path relative to the template root, without the suffix */
    public function __construct(public string $template)
    {
    }
}
