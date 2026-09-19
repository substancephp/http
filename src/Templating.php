<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * An application's HTML rendering configuration: where templates live, the source encoding, the default
 * layout, the {@see ShareInterface} services whose data is available to every template, and the asset helper
 * (see `docs/templating.md`).
 *
 * Passed to {@see Application::make()}. The framework's {@see RendererFactory} and the rendering middleware
 * read it back from the container.
 */
final readonly class Templating
{
    /**
     * @param non-empty-string $encoding
     * @param list<class-string<ShareInterface>> $shared {@see ShareInterface} services, in order; each must be
     *   registered as a container service
     * @param ?Assets $assets the asset helper's configuration, if the application uses `$this->asset()`
     */
    public function __construct(
        public string $root,
        public string $encoding = 'utf-8',
        public string $defaultLayout = 'layout',
        public array $shared = [],
        public ?Assets $assets = null,
    ) {
    }
}
