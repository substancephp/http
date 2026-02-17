<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Renderer;

use SubstancePHP\HTTP\RendererInterface;

class HtmlRenderer implements RendererInterface
{
    /** @param array<string, mixed> $data */
    public function __construct(
        private string $templatePath,
        private array $data,
    ) {
    }

    public function e(mixed $content): mixed
    {
        return \htmlspecialchars((string) $content, \ENT_QUOTES, 'UTF-8');
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
            $result = \ob_get_contents();
            if ($result === false) {
                throw new \Exception('Output buffering inactive');
            }
            return $result;
        } finally {
            \ob_end_clean();
        }
    }
}
