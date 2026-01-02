<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Http\Message\ResponseInterface;
use SubstancePHP\HTTP\Internal\ConditionalEmitter;

class Emitter implements EmitterInterface
{
    private EmitterStack $stack;

    public function __construct()
    {
        $this->stack = new EmitterStack();

        $sapiStreamEmitter = new SapiStreamEmitter();
        $conditionalEmitter = new ConditionalEmitter($sapiStreamEmitter);
        $sapiEmitter = new SapiEmitter();

        $this->stack->push($sapiEmitter);
        $this->stack->push($conditionalEmitter);
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function emit(ResponseInterface $response): bool
    {
        return $this->stack->emit($response);
    }
}
