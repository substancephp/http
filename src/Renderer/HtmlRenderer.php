<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Renderer;

use Laminas\Escaper\Escaper;
use SubstancePHP\HTTP\Exception\RenderingException\MissingPartialException;
use SubstancePHP\HTTP\RendererInterface;

class HtmlRenderer implements RendererInterface
{
    /**
     * Matches a PHP variable name: a letter, underscore or high byte, followed by letters, digits,
     * underscores or high bytes.
     */
    private const VALID_VARIABLE_NAME_PATTERN = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';

    /** @param array<string, mixed> $data */
    public function __construct(
        private string $templatePath,
        private array $data,
        private Escaper $escaper,
        private string $templateRoot,
    ) {
    }

    /** @internal transient: the template file currently being included. */
    private string $includePath = '';

    /**
     * @internal transient: the data of the template currently being included.
     * @var array<string, mixed>
     */
    private array $includeData = [];

    /** @deprecated Use {@see self::h()} instead */
    public function e(mixed $content): mixed
    {
        return \htmlspecialchars((string) $content, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Render render raw unescaped content */
    public function raw(mixed $content): string
    {
        return (string) $content;
    }

    /** Shorthand for {@see escapeHtml()} */
    public function h(string $content): string
    {
        return $this->escaper->escapeHtml($content);
    }

    public function escapeHtml(string $content): string
    {
        return $this->escaper->escapeHtml($content);
    }

    public function escapeHtmlAttr(string $content): string
    {
        return $this->escaper->escapeHtmlAttr($content);
    }

    /** Shorthand for {@see escapeHtmlAttr()} */
    public function a(string $content): string
    {
        return $this->escaper->escapeHtmlAttr($content);
    }

    public function escapeJs(string $content): string
    {
        return $this->escaper->escapeJs($content);
    }

    /** Shorthand for {@see escapeJs()} */
    public function j(string $content): string
    {
        return $this->escaper->escapeJs($content);
    }

    public function escapeCss(string $content): string
    {
        return $this->escaper->escapeCss($content);
    }

    /** Shorthand for {@see escapeCss()} */
    public function c(string $content): string
    {
        return $this->escaper->escapeCss($content);
    }

    public function escapeUrl(string $content): string
    {
        return $this->escaper->escapeUrl($content);
    }

    /** Shorthand for {@see escapeUrl()} */
    public function u(string $content): string
    {
        return $this->escaper->escapeUrl($content);
    }

    /**
     * Renders `partials/{name}.html.php` under the template root, returning the output as a string.
     * The partial receives only the passed `$data`, with `$this` bound to the renderer so the
     * escaping helpers are available. Trailing whitespace is trimmed from the output. Errors
     * thrown by the partial's own code propagate unchanged.
     *
     * @param string $name the partial to render; as template authors are trusted, it is used to
     *   build the file path directly
     * @param array<string, mixed> $data
     * @throws MissingPartialException if the partial template file does not exist
     * @throws \Exception if any data key cannot become a template variable
     */
    public function partial(string $name, array $data = []): string
    {
        $path = "{$this->templateRoot}/partials/{$name}.html.php";
        try {
            return \rtrim($this->renderFile($path, $data));
        } catch (\Error $e) {
            // Only a missing file is a MissingPartialException; any other \Error (e.g. from the
            // partial's own code) is rethrown as is.
            if (! \is_file($path)) {
                throw new MissingPartialException($path);
            }
            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $path, array $data): string
    {
        $started = \ob_start();
        if (! $started) {
            throw new \Exception('Could start output buffer');
        }
        try {
            $this->includeWith($path, $data);
            return (string) \ob_get_contents();
        } finally {
            \ob_end_clean();
        }
    }

    /**
     * Includes a template file with the given data.
     *
     * @param array<string, mixed> $data
     */
    private function includeWith(string $path, array $data): void
    {
        self::assertValidDataKeys($data);
        // These properties are read only inside the closure below, synchronously and before any
        // template code runs, so a nested include may safely overwrite them; the closure scope
        // holds only $this plus the extracted data variables, so data keys cannot collide.
        $this->includePath = $path;
        $this->includeData = $data;
        (function (): void {
            \extract($this->includeData);
            // Suppress PHP's warning for a missing file; the resulting \Error is the signal, which
            // callers translate into domain exceptions.
            @require $this->includePath;
        })();
    }

    /**
     * @param array<array-key, mixed> $data
     * @throws \Exception if any key cannot become a template variable
     */
    private static function assertValidDataKeys(array $data): void
    {
        foreach (\array_keys($data) as $key) {
            // 'this' is a valid identifier, but extract() never overwrites $this.
            $valid = \is_string($key)
                && ($key !== 'this')
                && (\preg_match(self::VALID_VARIABLE_NAME_PATTERN, $key) === 1);
            if (! $valid) {
                throw new \Exception('Invalid template data');
            }
        }
    }

    /** @throws \Exception */
    #[\Override]
    public function render(): string
    {
        $started = \ob_start();
        if (! $started) {
            throw new \Exception('Could start output buffer');
        }
        try {
            $this->includeWith($this->templatePath, $this->data);

            /**
             * To keep PHPStan happy: We know this is not false, since buffering is active, per above.
             *
             * @var string $result
             */
            $result = \ob_get_contents();

            return $result;
        } finally {
            \ob_end_clean();
        }
    }
}
