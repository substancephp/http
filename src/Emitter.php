<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use SubstancePHP\HTTP\Internal\ConditionalEmitter;

class Emitter extends EmitterStack implements EmitterInterface
{
    public function __construct()
    {
        $sapiStreamEmitter = new SapiStreamEmitter();
        $conditionalEmitter = new ConditionalEmitter($sapiStreamEmitter);
        $sapiEmitter = new SapiEmitter();

        $this->push($sapiEmitter);
        $this->push($conditionalEmitter);
    }
}
