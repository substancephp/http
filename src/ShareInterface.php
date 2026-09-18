<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A bundle of shared template variables: cross-cutting data that many templates need (CSRF fields, navigation
 * state, the current user, flash messages) but that no individual action should have to return.
 *
 * Register an implementation as a container service, then list its class in {@see Templating::$shared}. Once
 * per request, the framework resolves it from the request-scoped container and merges the variables it
 * returns into the variables available to every template (see `docs/templating.md`).
 */
interface ShareInterface
{
    /**
     * The variables this share contributes, keyed by the template variable name.
     *
     * @return array<string, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $context, ServerRequestInterface $request): array;
}
