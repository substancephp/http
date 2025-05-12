<?php

declare(strict_types=1);

namespace Test;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Emitter;

#[CoversClass(\SubstancePHP\HTTP\Emitter::class)]
#[CoversMethod(\SubstancePHP\HTTP\Emitter::class, '__construct')]
class EmitterTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $emitter = new Emitter();
        $this->assertInstanceOf(EmitterInterface::class, $emitter);
    }
}
