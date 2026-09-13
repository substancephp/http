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
    /**
     * @param array<MiddlewareInterface> $middlewares listed in order of OUTER to INNER.
     * @param list<class-string<MiddlewareInterface>> $skippedByDefault fully-qualified names of
     *   middlewares that are skipped unless a route opts them back in with
     *   {@see \SubstancePHP\HTTP\Middleware\Engage}.
     */
    public static function from(array $middlewares, array $skippedByDefault = []): self
    {
        return new self($middlewares, $skippedByDefault);
    }

    /**
     * @param array<MiddlewareInterface> $middlewares listed in order of OUTER to INNER.
     * @param list<class-string<MiddlewareInterface>> $skippedByDefault
     */
    private function __construct(
        private array $middlewares,
        private array $skippedByDefault = [],
    ) {
    }

    /**
     * Processes the request using the middleware stack.
     *
     * @throws EmptyMiddlewareStackException if there is no middleware in the stack.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middlewareStack = \array_reverse($this->middlewares);
        $handler = new MutableRequestHandler($middlewareStack, $this->skippedByDefault);
        return $handler->handle($request);
    }
}
