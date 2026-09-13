<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Internal;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\ConfigurableMiddlewareInterface;
use SubstancePHP\HTTP\Exception\BaseException\EmptyMiddlewareStackException;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;
use SubstancePHP\HTTP\Middleware\Configure;
use SubstancePHP\HTTP\Middleware\Engage;
use SubstancePHP\HTTP\Middleware\Skip;
use SubstancePHP\HTTP\Route;

/** @internal */
class MutableRequestHandler implements RequestHandlerInterface
{
    /** @var array<class-string<MiddlewareInterface>, true> */
    private readonly array $skippedByDefault;

    /** @var array<class-string<MiddlewareInterface>, true> */
    private readonly array $registeredMiddleware;

    /** The route whose declarations have already been validated (avoids revalidating per middleware). */
    private ?Route $validatedRoute = null;

    /**
     * @param MiddlewareInterface[] $middlewareStack middlewares in INSIDE-to-OUT order
     * @param list<class-string<MiddlewareInterface>> $skippedByDefault
     */
    public function __construct(
        private array $middlewareStack,
        array $skippedByDefault,
    ) {
        $this->skippedByDefault = \array_fill_keys($skippedByDefault, true);
        $this->registeredMiddleware = \array_fill_keys(\array_map(\get_class(...), $middlewareStack), true);
    }

    /**
     * @throws EmptyMiddlewareStackException if there is no middleware in the stack.
     * @throws InvalidMiddlewareException if the route declares a middleware that is not registered.
     * @throws \ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $request->getAttribute(Route::class);
        if (! ($route instanceof Route)) {
            $route = null;
        } elseif ($this->validatedRoute !== $route) {
            $this->validateRoute($route);
            $this->validatedRoute = $route;
        }
        do {
            if (\count($this->middlewareStack) == 0) {
                throw new EmptyMiddlewareStackException('Middleware stack empty');
            }
            $middleware = \array_pop($this->middlewareStack);
        } while (($route !== null) && $this->shouldSkip($route, \get_class($middleware)));

        // A configurable middleware always receives a parsed config; a route that declares none yields
        // the middleware's defaults via parseConfig([]).
        if ($middleware instanceof ConfigurableMiddlewareInterface) {
            $class = \get_class($middleware);
            if ($route === null) {
                $config = [];
            } else {
                $config = $route->configFor($class) ?? [];
            }
            $request = $request->withAttribute($class, $middleware->parseConfig($config));
        }
        return $middleware->process($request, $this);
    }

    /**
     * Whether the named middleware should be skipped for the route. A route-level {@see Engage} or
     * {@see Configure} opts a middleware back in (and wins over a route-level {@see Skip}, though
     * declaring both is an error); otherwise a route-level {@see Skip}, or the application-wide
     * "disabled by default", skips it.
     *
     * @throws \ReflectionException
     */
    private function shouldSkip(Route $route, string $middleware): bool
    {
        if ($route->shouldEngage($middleware) || $route->hasConfigFor($middleware)) {
            return false;
        }
        if ($route->shouldSkip($middleware)) {
            return true;
        }
        return isset($this->skippedByDefault[$middleware]);
    }

    /**
     * @throws InvalidMiddlewareException if the route references a middleware that is not registered, or
     *   configures a middleware that is not {@see ConfigurableMiddlewareInterface}.
     * @throws \ReflectionException
     */
    private function validateRoute(Route $route): void
    {
        $referenced = [
            ...$route->skippedMiddlewares(),
            ...$route->engagedMiddlewares(),
            ...$route->configuredMiddlewares(),
        ];
        foreach ($referenced as $middleware) {
            if (! isset($this->registeredMiddleware[$middleware])) {
                throw new InvalidMiddlewareException(
                    "Route references a middleware that is not registered: {$middleware}",
                );
            }
        }
        foreach ($route->configuredMiddlewares() as $middleware) {
            if (! \is_a($middleware, ConfigurableMiddlewareInterface::class, true)) {
                throw new InvalidMiddlewareException(
                    "Route configures a middleware that is not configurable: {$middleware}",
                );
            }
        }
    }
}
