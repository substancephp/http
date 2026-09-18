<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * An application's HTML rendering configuration: where templates live, the source encoding, the default
 * layout, and the {@see ShareInterface} services whose data is available to every template (see
 * `docs/templating.md`).
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
     */
    public function __construct(
        public string $root,
        public string $encoding = 'utf-8',
        public string $defaultLayout = 'layout',
        public array $shared = [],
    ) {
    }
}
