<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * The inputs required to render one response body: the renderer path, the response content type, the data
 * returned by the action, and the application's shared template variables.
 *
 * `path` is the route's normalized path (e.g. `stores/[id]`) for an action response, or the error-template
 * path (e.g. `error/422`) for an error page; the renderer appends the suffix for its template format.
 *
 * `shared` (see `Application::make(shared: ...)`) is the application's shared template variables. It may be
 * an array or a factory returning one; a factory is invoked only when an HTML renderer is actually built,
 * so responses of other content types pay nothing for it.
 */
final readonly class RenderInput
{
    /** @param array<string, mixed>|\Closure(): array<string, mixed> $shared */
    public function __construct(
        public string $path,
        public string $contentType,
        public mixed $data,
        public array|\Closure $shared = [],
    ) {
    }
}
