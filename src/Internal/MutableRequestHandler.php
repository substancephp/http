<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Internal;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\EmptyMiddlewareStackException;

/** @internal */
class MutableRequestHandler implements RequestHandlerInterface
{
    /** @param MiddlewareInterface[] $middlewareStack stack of middlewares, listed in order of INSIDE to OUT */
    public function __construct(private array $middlewareStack)
    {
    }

    /** @throws EmptyMiddlewareStackException if there is no middleware in the stack. */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (\count($this->middlewareStack) == 0) {
            throw new EmptyMiddlewareStackException('Middleware stack empty');
        }
        $outermostMiddleware = \array_pop($this->middlewareStack);
        return $outermostMiddleware->process($request, $this);
    }
}
