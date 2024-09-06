<?php

namespace SubstancePHP\HTTP;

use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Middleware\Skip;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Represents a route for handling HTTP requests based on matching a filepath to the path of the
 * request URL, and then invoking a callback defined at that filepath.
 *
 * For example, a request to PATCH /assets/vehicles would be handled by a route created as follows:
 *
 * <code>
 *     $route = Route::from('/path/to/project/actions', 'PATCH', '/assets/vehicles');
 * </code>
 *
 * Later, when handling `$route`, it would be expected that a file will reside at
 * `/path/to/project/actions/assets/vehicles.patch.php`, with that file returning an appropriate
 * callback for handling the request.
 */
class Route
{
    private \Closure $callback;

    /** @var string[] fully-qualified names of PSR-15 middleware classes that should be skipped by this route */
    private ?array $skippableMiddlewares;

    private function __construct(callable $callback)
    {
        $this->callback = $callback(...);
        $this->skippableMiddlewares = null;
    }

    /**
     * @param string $actionRoot the directory path under which the filesystem based action handlers
     *   are located.
     * @param string $method the HTTP method handled by this route
     * @param string $path the URL path
     * @return ?self a new instance; or null if either: there is no file matching this route; or there is a file
     *   but it does not return a callable.
     */
    public static function from(string $actionRoot, string $method, string $path): ?self
    {
        $lowerMethod = \strtolower($method);
        $filepath = "$actionRoot/$path.$lowerMethod.php";
        if (! \file_exists($filepath)) {
            return null;
        }
        $content = require $filepath;
        if (! \is_callable($content)) {
            return null;
        }
        return new self($content);
    }

    /**
     * The route callback may be annotated with the Skip attribute, indicating that certain middlewares should be
     * skipped when handling the route.
     *
     * When passed a fully qualified class name, this method returns true if and only if the corresponding
     * middleware has been indicated in this way.
     *
     * @throws \ReflectionException
     */
    public function shouldSkip(string $middleware): bool
    {
        return \in_array(
            $middleware,
            $this->skippableMiddlewares ??= $this->computeSkippableMiddlewares(),
            true,
        );
    }

    /**
     * @return array<string> fully-qualified class names of middlewares that should be skipped in handling this route
     * @throws \ReflectionException
     */
    private function computeSkippableMiddlewares(): array
    {
        $skippableMiddlewares = [];
        $reflectionFunction = new \ReflectionFunction($this->callback);
        $reflectionAttributes = $reflectionFunction->getAttributes();
        foreach ($reflectionAttributes as $reflectionAttribute) {
            if ($reflectionAttribute->getName() === Skip::class) {
                $attribute = $reflectionAttribute->newInstance();
                \assert($attribute instanceof Skip);
                foreach ($attribute->skippableMiddlewares as $skippableMiddleware) {
                    $skippableMiddlewares[] = $skippableMiddleware;
                }
            }
        }
        return $skippableMiddlewares;
    }

    /**
     * Execute the route callback, using the passed Container to inject dependencies.
     *
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function execute(Container $context): mixed
    {
        return $context->run($this->callback);
    }
}
