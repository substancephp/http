<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\EmptyMiddlewareStackException;
use SubstancePHP\HTTP\Internal\MutableRequestHandler;

/** Processes HTTP requests by passing them through a series of middlewares. */
readonly class RequestHandler implements RequestHandlerInterface
{
    /** @param array<MiddlewareInterface> $middlewares listed in order of OUTER to INNER. */
    public static function from(array $middlewares): self
    {
        return new self($middlewares);
    }

    /** @param array<MiddlewareInterface> $middlewares listed in order of OUTER to INNER. */
    private function __construct(private array $middlewares)
    {
    }

    /**
     * Processes the request using the middleware stack.
     *
     * @throws EmptyMiddlewareStackException if there is no middleware in the stack.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middlewareStack = \array_reverse($this->middlewares);
        $handler = new MutableRequestHandler($middlewareStack);
        return $handler->handle($request);
    }
}
