# Middleware

Middleware is registered on the application in OUTER-to-INNER order. A route can skip a middleware,
opt a default-disabled one back in, and configure a configurable one.

## Register middleware

```php
use SubstancePHP\HTTP\Application;
use SubstancePHP\HTTP\Templating;

Application::make(
    env: $_ENV,
    actionRoot: __DIR__ . '/actions',
    providers: [AppProvider::class, SubstanceProvider::class],
    middlewares: [
        SecurityMiddleware::class,
        RateLimiterMiddleware::class,
        RouteMatcherMiddleware::class,
    ],
    templating: new Templating(__DIR__ . '/templates'),
);
```

## Disable a middleware by default

Wrap a class in `MiddlewareSpec` to register it but skip it unless a route opts in:

```php
use SubstancePHP\HTTP\MiddlewareSpec;

middlewares: [
    DebugBarMiddleware::class,
    MiddlewareSpec::disable(ExpensiveAuditMiddleware::class),
]
```

## Skip and engage per route

Both attributes take a single middleware and may be repeated:

```php
use SubstancePHP\HTTP\Middleware\Engage;
use SubstancePHP\HTTP\Middleware\Skip;

// This route runs ExpensiveAuditMiddleware (which is disabled by default), and skips CSRF.
return
    #[Engage(ExpensiveAuditMiddleware::class)]
    #[Skip(CsrfMiddleware::class)]
    static function (): mixed {
        return ['data' => []];
    };
```

## Configure middleware per route

A middleware that implements `ConfigurableMiddlewareInterface` accepts per-route configuration via
`#[Configure(...)]`:

```php
use SubstancePHP\HTTP\Middleware\Configure;

return #[Configure(RateLimiterMiddleware::class, ['rate' => 30])] static function (): mixed {
    return ['data' => []];
};
```

`#[Configure]` also counts as engaging the middleware, so a default-disabled one needs no separate
`#[Engage]`. A route may not `#[Skip]` and `#[Configure]` the same middleware.

## Writing a configurable middleware

`parseConfig()` validates the raw array and returns a typed configuration; the middleware reads it back
with `MiddlewareConfig::for()`:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\ConfigurableMiddlewareInterface;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;
use SubstancePHP\HTTP\MiddlewareConfig;

/** @implements ConfigurableMiddlewareInterface<RateLimitConfig> */
final readonly class RateLimiterMiddleware implements ConfigurableMiddlewareInterface
{
    public function parseConfig(array $config): RateLimitConfig
    {
        $rate = $config['rate'] ?? 60;
        if (! \is_int($rate)) {
            throw new InvalidMiddlewareException('"rate" must be an int');
        }
        return new RateLimitConfig(rate: $rate);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $config = MiddlewareConfig::for($request, self::class);   // RateLimitConfig
        // ... use $config->rate ...
        return $handler->handle($request);
    }
}
```

`parseConfig()` runs once per request, before the middleware runs, with the array declared for the
route. When a route does not configure the middleware it is called with `[]`, so it must return the
middleware's defaults.

The config type is declared once (`parseConfig()`'s return type) and `MiddlewareConfig::for()` infers
the same type from `self::class`, so the two can't drift apart.

## More examples

```php
// Per-route rate limit.
#[Configure(RateLimiterMiddleware::class, ['rate' => 5])]

// Per-route authorization.
#[Configure(AuthorizationMiddleware::class, ['allow' => ['admin', 'manager']])]

// Per-route cache lifetime.
#[Configure(HttpCacheMiddleware::class, ['ttl' => 300])]
```
