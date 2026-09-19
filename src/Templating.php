<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * An application's HTML rendering configuration: where templates live, the source encoding, the default
 * layout, the {@see ShareInterface} whose public properties are available to every template, and the asset
 * helper (see `docs/templating.md`).
 *
 * Passed to {@see Application::make()}. The framework's {@see RendererFactory} and the rendering middleware
 * read it back from the container.
 */
final readonly class Templating
{
    /**
     * @param non-empty-string $encoding
     * @param class-string<ShareInterface> $share the share whose public properties become template
     *   variables; bind that class as a request-scoped container service to configure it, or let the
     *   framework autowire it when its constructor dependencies are all resolvable
     * @param ?Assets $assets the asset helper's configuration, if the application uses `$this->asset()`
     */
    public function __construct(
        public string $root,
        public string $encoding = 'utf-8',
        public string $defaultLayout = 'layout',
        public string $share = EmptyShare::class,
        public ?Assets $assets = null,
    ) {
    }
}
