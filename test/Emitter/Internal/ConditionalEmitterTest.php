<?php

declare(strict_types=1);

namespace Test\Emitter\Internal;

use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use SubstancePHP\HTTP\Emitter\Internal\ConditionalEmitter;

#[CoversClass(ConditionalEmitter::class)]
#[CoversMethod(ConditionalEmitter::class, 'emit')]
#[CoversMethod(ConditionalEmitter::class, '__construct')]
class ConditionalEmitterTest extends TestCase
{
    #[Test]
    #[TestWith([false, false, false])]
    #[TestWith([false, true, true])]
    #[TestWith([true, false, true])]
    #[TestWith([true, true, true])]
    public function emit(bool $hasDisposition, bool $hasRange, bool $expectEmit): void
    {
        $emitter = new class implements EmitterInterface {
            public bool $emitted = false;

            /**
             * @inheritDoc
             */
            #[\Override] public function emit(ResponseInterface $response): bool
            {
                return ($this->emitted = true);
            }
        };

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        if ($hasDisposition) {
            $response = $response->withHeader('Content-Disposition', 'inline');
        }
        if ($hasRange) {
            $response = $response->withHeader('Content-Range', 'bytes 0-1023/2000');
        }
        $instance = new ConditionalEmitter($emitter);
        $result = $instance->emit($response);
        $this->assertSame($expectEmit, $result);
        $this->assertSame($expectEmit, $emitter->emitted);
    }
}
