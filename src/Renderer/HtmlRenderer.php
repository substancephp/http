<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Renderer;

use Laminas\Escaper\Escaper;
use SubstancePHP\HTTP\RendererInterface;

class HtmlRenderer implements RendererInterface
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private string $templatePath,
        private array $data,
        private Escaper $escaper,
    ) {
    }

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

    /** @throws \Exception */
    #[\Override]
    public function render(): string
    {
        $started = \ob_start();
        if (! $started) {
            throw new \Exception('Could start output buffer');
        }
        try {
            if (\extract($this->data) != \count($this->data)) {
                throw new \Exception('Invalid template data');
            }
            require $this->templatePath;

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
