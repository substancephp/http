<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Internal;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

// See https://docs.laminas.dev/laminas-httphandlerrunner/emitters/

/**
 * @internal
 */
readonly class ConditionalEmitter implements EmitterInterface
{
    public function __construct(private EmitterInterface $emitter)
    {
    }

    #[\Override]
    public function emit(ResponseInterface $response): bool
    {
        if (! $response->hasHeader('Content-Disposition') && ! $response->hasHeader('Content-Range')) {
            return false;
        }
        return $this->emitter->emit($response);
    }
}
