<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\EmptyMiddlewareStackException;

/** Processes HTTP requests by passing them through a series of middlewares. */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * @param array<MiddlewareInterface> $middlewares listed in order of OUTER to INNER.
     */
    public static function from(array $middlewares): self
    {
        return new self(\array_reverse($middlewares));
    }

    /**
     * Processes the request using the middleware stack.
     *
     * @throws EmptyMiddlewareStackException if there is no middleware in the stack.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (\count($this->middlewareStack) == 0) {
            throw new EmptyMiddlewareStackException('Middleware stack empty');
        }
        $outermostMiddleware = \array_pop($this->middlewareStack);
        return $outermostMiddleware->process($request, $this);
    }

    /** @param MiddlewareInterface[] $middlewareStack stack of middlewares, listed in order of INSIDE to OUT */
    private function __construct(private array $middlewareStack)
    {
    }
}
