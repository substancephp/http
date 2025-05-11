<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class Emitter extends EmitterStack implements EmitterInterface
{
    public function __construct()
    {
        // From https://docs.laminas.dev/laminas-httphandlerrunner/emitters/
        $conditionalEmitter = new readonly class (new SapiStreamEmitter()) implements EmitterInterface {
            public function __construct(private EmitterInterface $emitter)
            {
            }

            public function emit(ResponseInterface $response): bool
            {
                if (! $response->hasHeader('Content-Disposition') && ! $response->hasHeader('Content-Range')) {
                    return false;
                }
                return $this->emitter->emit($response);
            }
        };

        $sapiEmitter = new SapiEmitter();

        $this->push($sapiEmitter);
        $this->push($conditionalEmitter);
    }
}
