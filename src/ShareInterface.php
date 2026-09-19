<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * Shared template variables: cross-cutting data that many templates need (CSRF fields, navigation state, the
 * current user, flash messages) but that no individual action should have to return.
 *
 * An application implements this interface once, on a class whose *public properties* are the variables, and
 * names that class in {@see Templating::$share}:
 *
 * <code>
 *     final readonly class AppShared implements ShareInterface
 *     {
 *         public function __construct(
 *             public string $appName,
 *             public ?string $flash,
 *         ) {
 *         }
 *     }
 * </code>
 *
 * The framework resolves it from the request-scoped container once per request, so its constructor may
 * require request-scoped dependencies, and makes its public properties available to every template layer
 * (view, layout, partial and element) as plain variables. Action data wins on a name clash.
 *
 * The set of names is fixed: a variable that only some requests have is a nullable property, so a template
 * can rely on every shared variable being defined. See `docs/templating.md`.
 *
 * The interface is a marker: it names the role, and it is what {@see Templating::$share} accepts. The
 * framework reads the public properties, so it never calls anything on an implementation.
 */
interface ShareInterface
{
}
