<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Renderer;

use Laminas\Escaper\Escaper;
use SubstancePHP\HTTP\Exception\RenderingException\MissingElementException;
use SubstancePHP\HTTP\Exception\RenderingException\MissingLayoutException;
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
        private string $defaultLayout = 'layout',
    ) {
    }

    /** @internal transient: the template file currently being included. */
    private string $includePath = '';

    /**
     * @internal transient: the data of the template currently being included.
     * @var array<string, mixed>
     */
    private array $includeData = [];

    /** @internal transient: the layout name declared by the current template. */
    private string $layoutName = '';

    /**
     * @internal transient: the layout data declared by the current template.
     * @var array<string, mixed>
     */
    private array $layoutData = [];

    /** @internal transient: whether the current template declared a layout. */
    private bool $layoutSet = false;

    /**
     * @internal transient: stack of inner outputs, read by {@see self::content()}.
     * @var string[]
     */
    private array $contentStack = [];

    /**
     * @internal transient: stack of slot maps; the bottom scope is the page scope.
     * @var array<string, string>[]
     */
    private array $slotScopes = [[]];

    /**
     * @internal transient: open slot captures, each a `[name, mode]` pair.
     * @var array{string, string}[]
     */
    private array $sectionStack = [];

    /**
     * @internal transient: open elements, each with its name and params.
     * @var array{name: string, params: array<string, mixed>}[]
     */
    private array $elementStack = [];

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
     * Declares the layout wrapping the current view or layout, replacing the default.
     *
     * @param array<string, mixed> $data data visible only to this layout layer
     */
    public function layout(string $name, array $data = []): void
    {
        $this->layoutName = $name;
        $this->layoutData = $data;
        $this->layoutSet = true;
    }

    /** The rendered output of the layer beneath the current template, or '' for the innermost layer. */
    public function content(): string
    {
        $count = \count($this->contentStack);
        return $count == 0 ? '' : (string) $this->contentStack[$count - 1];
    }

    /** Starts capturing output into the named slot of the current scope, replacing any previous value. */
    public function start(string $name): void
    {
        $this->beginCapture($name, 'set');
    }

    /** Starts capturing output to append to the named slot of the current scope. */
    public function append(string $name): void
    {
        $this->beginCapture($name, 'append');
    }

    /** Starts capturing output to prepend to the named slot of the current scope. */
    public function prepend(string $name): void
    {
        $this->beginCapture($name, 'prepend');
    }

    private function beginCapture(string $name, string $mode): void
    {
        $this->sectionStack[] = [$name, $mode];
        \ob_start();
    }

    /**
     * Ends the innermost open capture and stores it in the named slot of the current scope.
     *
     * @throws \LogicException if there is no open capture
     */
    public function stop(): void
    {
        $capture = \array_pop($this->sectionStack);
        if ($capture === null) {
            throw new \LogicException('stop() called with no open capture');
        }
        [$name, $mode] = $capture;
        $out = (string) \ob_get_clean();
        $scopeIndex = \count($this->slotScopes) - 1;
        $existing = $this->slotScopes[$scopeIndex][$name] ?? '';
        $this->slotScopes[$scopeIndex][$name] = match ($mode) {
            'append' => $existing . $out,
            'prepend' => $out . $existing,
            default => $out,
        };
    }

    /**
     * The named slot of the current scope, or $default if the slot was never filled. An explicitly
     * empty slot stays empty.
     */
    public function fetch(string $name, ?string $default = null): string
    {
        $scope = $this->slotScopes[\count($this->slotScopes) - 1];
        return $scope[$name] ?? $default ?? '';
    }

    /**
     * Starts the element named at the call site, capturing its body until {@see self::endElement()}.
     *
     * @param array<string, mixed> $params passed to the element template as plain variables
     */
    public function beginElement(string $name, array $params = []): void
    {
        $this->elementStack[] = ['name' => $name, 'params' => $params];
        $this->slotScopes[] = [];
        \ob_start();
    }

    /**
     * Ends the innermost element: renders its template with the captured body as {@see self::content()}
     * and the element's sub-slots via {@see self::fetch()}.
     *
     * @throws \LogicException if there is no open element, or an unclosed slot capture
     * @throws MissingElementException if the element template file does not exist
     */
    public function endElement(): void
    {
        $element = \array_pop($this->elementStack);
        if ($element === null) {
            throw new \LogicException('endElement() called with no open element');
        }
        if (\count($this->sectionStack) > 0) {
            throw new \LogicException('endElement() called with an unclosed capture');
        }
        $body = (string) \ob_get_clean();
        // Every open element pushed exactly one slot scope, so the pop below cannot be null.
        $subslots = \array_pop($this->slotScopes);
        \assert($subslots !== null);
        $this->contentStack[] = $body;
        // Re-push the element's own scope so its template can fetch() its sub-slots.
        $this->slotScopes[] = $subslots;
        // An element must not be able to change the layout of the template that contains it.
        $savedLayout = [$this->layoutSet, $this->layoutName, $this->layoutData];
        try {
            $path = "{$this->templateRoot}/elements/{$element['name']}.html.php";
            try {
                $rendered = $this->renderFile($path, $element['params']);
            } catch (\Error $e) {
                // Only a missing file is a MissingElementException; any other \Error (e.g. from the
                // element's own code) is rethrown as is.
                if (! \is_file($path)) {
                    throw new MissingElementException($path);
                }
                throw $e;
            }
            // The element template's output is already-rendered markup, so echo it unescaped.
            echo $this->raw(\rtrim($rendered));
        } finally {
            [$this->layoutSet, $this->layoutName, $this->layoutData] = $savedLayout;
            \array_pop($this->slotScopes);
            \array_pop($this->contentStack);
        }
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
        // A partial must not be able to change the layout of the template that includes it.
        $savedLayout = [$this->layoutSet, $this->layoutName, $this->layoutData];
        try {
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
        } finally {
            [$this->layoutSet, $this->layoutName, $this->layoutData] = $savedLayout;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @phpstan-impure includes a template, which may mutate the renderer (e.g. via layout())
     */
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
     * @param array<string, mixed> $data
     * @throws MissingLayoutException if the layout template file does not exist
     * @phpstan-impure includes a template, which may mutate the renderer (e.g. via layout())
     */
    private function renderLayout(string $name, array $data): string
    {
        $path = "{$this->templateRoot}/layouts/{$name}.html.php";
        try {
            return $this->renderFile($path, $data);
        } catch (\Error $e) {
            // Only a missing file is a MissingLayoutException; any other \Error (e.g. from the
            // layout's own code) is rethrown as is.
            if (! \is_file($path)) {
                throw new MissingLayoutException($path);
            }
            throw $e;
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
                && (\preg_match(self::VALID_VARIABLE_NAME_PATTERN, $key) == 1);
            if (! $valid) {
                throw new \Exception('Invalid template data');
            }
        }
    }

    /**
     * Renders the view and wraps its output in its declared layout, or the default layout when none
     * is declared. A layout may itself declare a layout, wrapping outward; each layer reads the
     * layer beneath it via {@see self::content()}.
     *
     * @throws MissingLayoutException if a layout template file does not exist
     * @throws \LogicException if a layout declares itself, directly or via a cycle
     * @throws \Exception if the view or layout data contains an invalid key
     */
    #[\Override]
    public function render(): string
    {
        $this->layoutSet = false;
        $this->contentStack = [];
        $this->slotScopes = [[]];
        $this->sectionStack = [];
        $this->elementStack = [];
        $out = $this->renderFile($this->templatePath, $this->data);
        $layout = $this->layoutSet ? [$this->layoutName, $this->layoutData] : [$this->defaultLayout, []];
        $visited = [];
        while ($layout !== null) {
            [$name, $layoutData] = $layout;
            if (isset($visited[$name])) {
                throw new \LogicException("Circular layout declaration: {$name}");
            }
            $visited[$name] = true;
            $this->layoutSet = false;
            $this->contentStack[] = $out;
            try {
                $out = $this->renderLayout($name, $layoutData);
            } finally {
                \array_pop($this->contentStack);
            }
            $layout = $this->layoutSet ? [$this->layoutName, $this->layoutData] : null;
        }
        return $out;
    }
}
