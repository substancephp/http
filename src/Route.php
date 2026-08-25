<?php

namespace SubstancePHP\HTTP;

use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Middleware\Skip;
use SubstancePHP\HTTP\RequestParams\PathParams;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Represents a route for handling HTTP requests based on matching a filepath to the path of the
 * request URL, and then invoking a callback defined at that filepath.
 *
 * For example, a request to PATCH /assets/stores would be handled by a route created as follows:
 *
 * <code>
 *     $route = Route::from('/path/to/project/actions', 'PATCH', '/assets/stores');
 * </code>
 *
 * Later, when handling `$route`, it would be expected that a file will reside at
 * `/path/to/project/actions/assets/stores.patch.php`, with that file returning an appropriate
 * callback for handling the request.
 *
 * Path parameters:
 *
 * Any path segment may be dynamic. An intermediate dynamic segment is declared as a directory named
 * <code>[name]</code> (e.g. <code>accounts/[id]/profile.get.php</code>); the leaf dynamic segment is
 * declared as a file named <code>[name].{method}.php</code> (e.g.
 * <code>stores/[id].get.php</code>). Resolution is literal-first at every segment: an exact
 * filesystem match always wins over a parameter. The route's {@see Route::normalizedPath} is the
 * declared path, with dynamic segments written as <code>[name]</code> (e.g. a request to
 * <code>/stores/42</code> yields <code>stores/[id]</code>); this is also the path used to resolve HTML
 * templates (e.g. <code>stores/[id].html.php</code>). The captured segments (URL-decoded) are exposed via
 * {@see PathParams}; e.g. <code>/accounts/42/posts/99</code> resolves
 * <code>accounts/[id]/posts/[postId].get.php</code> with <code>$params['id'] === '42'</code> and
 * <code>$params['postId'] === '99'</code> when no literal counterpart exists. If the same parameter
 * name is declared on more than one segment, the later segment's value wins.
 *
 * It is expected that the returned callback will return an {@see Out} instance.
 */
class Route
{
    /** The normalized path of the synthetic root route (`/`) */
    private const ROOT_PATH = '_root';

    private \Closure $callback;

    /** The normalized declared path of the route, with dynamic segments written as `[name]` */
    public readonly string $normalizedPath;

    /** @var array<string, string> captured path parameters, keyed by the segment name declared in `[name]` */
    private readonly array $params;

    /** @var string[] fully-qualified names of PSR-15 middleware classes this route should skip */
    private ?array $skippableMiddlewares;

    /** @param array<string, string> $params the captured path parameters */
    private function __construct(callable $callback, string $normalizedPath, array $params)
    {
        $this->callback = $callback(...);
        $this->normalizedPath = $normalizedPath;
        $this->params = $params;
        $this->skippableMiddlewares = null;
    }

    /**
     * @param string $actionRoot the directory path under which the filesystem based action handlers
     *   are located.
     * @param string $method the HTTP method handled by this route
     * @param string $path the URL path
     * @return ?self a new instance; or null if either: there is no file matching this route; or there is
     *   a file but it does not return a callable.
     */
    public static function from(string $actionRoot, string $method, string $path): ?self
    {
        $lowerMethod = \strtolower($method);

        $requestPath = \trim($path, '/');
        if ($requestPath === '') {
            $segments = [self::ROOT_PATH];
        } else {
            $segments = \explode('/', $requestPath);
        }

        $dir = \rtrim($actionRoot, '/');
        $params = [];
        $declaredSegments = [];
        $lastIndex = \count($segments) - 1;
        foreach ($segments as $index => $segment) {
            $isLeaf = ($index === $lastIndex);

            // Empty or dot segments (e.g. "/a//b" or "/a/../b") match neither a literal nor a
            // parameter; rejecting ".." also keeps resolution inside the action root.
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }

            // Literal first: an exact match always wins over a parameter.
            if (! $isLeaf) {
                $literalDir = "$dir/$segment";
                if (\is_dir($literalDir)) {
                    $dir = $literalDir;
                    $declaredSegments[] = $segment;
                    continue;
                }

                // Fall back to a single named parameter directory declared in this directory.
                $paramName = self::findParamDirName($dir);
                if ($paramName === null) {
                    return null;
                }
                $params[$paramName] = \rawurldecode($segment);
                $dir = "$dir/[$paramName]";
                $declaredSegments[] = "[$paramName]";
                continue;
            }

            $literalActionPath = "$dir/$segment.$lowerMethod.php";
            if (\file_exists($literalActionPath)) {
                $declaredSegments[] = $segment;
                return self::load($literalActionPath, \implode('/', $declaredSegments), $params);
            }

            // The root sentinel path is not eligible for parameter capture.
            if ($requestPath === '' || $requestPath === self::ROOT_PATH) {
                return null;
            }

            // Fall back to a single named parameter declared in this directory.
            $paramName = self::findParamName($dir, $lowerMethod);
            if ($paramName === null) {
                return null;
            }
            $params[$paramName] = \rawurldecode($segment);
            $declaredSegments[] = "[$paramName]";
            return self::load("$dir/[$paramName].$lowerMethod.php", \implode('/', $declaredSegments), $params);
        }

        return null;
    }

    /**
     * @param array<string, string> $params
     * @return ?self a new instance; or null if the file exists but does not return a callable.
     */
    private static function load(string $actionPath, string $normalizedPath, array $params): ?self
    {
        $content = require $actionPath;
        if (! \is_callable($content)) {
            return null;
        }
        return new self($content, $normalizedPath, $params);
    }

    /**
     * Scans a directory for the single named parameter its entries may declare, as a leaf file
     * <code>[name].{method}.php</code>. Returns null when no such entry exists.
     *
     * <code>scandir</code> is used (not <code>glob</code>) because brackets, the parameter delimiters,
     * are glob metacharacters.
     */
    private static function findParamName(string $dir, string $method): ?string
    {
        return self::findParamEntry($dir, '/^\[([^\]]+)\]\.' . \preg_quote($method, '/') . '\.php$/');
    }

    /**
     * Scans a directory for the single named parameter its subdirectories may declare, as a directory
     * <code>[name]</code>. Returns null when no such entry exists.
     */
    private static function findParamDirName(string $dir): ?string
    {
        return self::findParamEntry($dir, '/^\[([^\]]+)\]$/');
    }

    /**
     * Scans a directory for the first entry matching the given parameter-declaration pattern, and
     * returns the declared parameter name.
     */
    private static function findParamEntry(string $dir, string $pattern): ?string
    {
        if (! \is_dir($dir)) {
            return null;
        }
        $entries = \scandir($dir, \SCANDIR_SORT_ASCENDING);
        if ($entries === false) {
            return null;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (\preg_match($pattern, $entry, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * The route callback may be annotated with the Skip attribute, indicating that certain middlewares
     * should be skipped when handling the route.
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

    /** @return array<string, string> the captured path parameters, keyed by their `[name]` declaration */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return array<string> fully-qualified class names of middlewares that should be skipped in handling
     *   this route
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
