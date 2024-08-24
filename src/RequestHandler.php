<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Processes HTTP requests by passing them through a series of middlewares. */
readonly class RequestHandler implements RequestHandlerInterface
{
    /**
     * @param array<MiddlewareInterface> $middlewares listed in order of OUTER to INNER.
     */
    public static function from(array $middlewares): self
    {
        return new self(\array_reverse($middlewares));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$outermostMiddleware, $nextHandler] = $this->pop();
        return $outermostMiddleware->process($request, $nextHandler);
    }

    /** @param MiddlewareInterface[] $middlewareStack stack of middlewares, listed in order of INSIDE to OUT */
    private function __construct(private array $middlewareStack)
    {
    }

    /** @return array{MiddlewareInterface, RequestHandlerInterface} the top middleware, and the next handler */
    private function pop(): array
    {
        $newMiddlewareStack = $this->middlewareStack;
        if (\count($newMiddlewareStack) == 0) {
            throw new \RuntimeException('Middleware stack unexpectedly empty');
        }
        $outermostMiddleware = \array_pop($newMiddlewareStack);
        \assert($outermostMiddleware !== null);
        $nextHandler = match (\count($newMiddlewareStack)) {
            0 => $this->getFinalHandler(),
            default => new self($newMiddlewareStack),
        };
        return [$outermostMiddleware, $nextHandler];
    }

    private function getFinalHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Middleware stack empty');
            }
        };
    }
}
