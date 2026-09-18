<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\ContextFactoryInterface;
use SubstancePHP\HTTP\Exception\BaseException\RoutingException;
use SubstancePHP\HTTP\RendererFactoryInterface;
use SubstancePHP\HTTP\RenderInput;
use SubstancePHP\HTTP\Respond;
use SubstancePHP\HTTP\Route;
use SubstancePHP\HTTP\Shares;

/**
 * This middleware assumes there is a {@see Route} stored on the request it is processing. It uses the
 * information in the {@see Route} to handle the "meat" of the request. This will typically involve running
 * the route's callback, converting its return value into an HTTP response, and returning the latter.
 *
 * The response is materialized from the request-scoped {@see Respond} (its status code and headers)
 * together with the value the action returned. If the status code forbids a body (1xx, 204, 304) or the
 * action removed the `Content-Type` header, no renderer runs and no `Content-Type` is emitted; otherwise
 * the `Content-Type` header selects the renderer that turns the returned value into the body.
 */
readonly class RouteActorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ContextFactoryInterface $contextFactory,
        private RendererFactoryInterface $rendererFactory,
        private ResponseFactoryInterface $responseFactory,
        private Shares $shares,
    ) {
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RoutingException if there is no {@see Route} stored on the request.
     * @throws \JsonException if the content returned by the route's action, cannot be JSON-encoded.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $route = $request->getAttribute(Route::class);
        if (! ($route instanceof Route)) {
            throw new RoutingException('Route attribute not an instance of ' . Route::class);
        }
        $context = $this->contextFactory->createContext($this->container, $request);
        $responseData = $route->execute($context);

        /** @var Respond $respond */
        $respond = $context->get(Respond::class);
        $statusCode = $respond->getStatusCode();
        $contentType = $respond->getHeaderLine('Content-Type');

        // A response has no body when its status code forbids one, or when the action removed the
        // `Content-Type` header (as {@see Respond::redirectTo()} does). In that case no renderer runs
        // and no `Content-Type` is emitted, though any other headers the action set are still applied.
        $bodyless = self::isBodylessStatus($statusCode) || ($contentType === '');

        $response = $this->responseFactory->createResponse($statusCode);
        foreach ($respond->getHeaders() as $name => $values) {
            if ($bodyless && (\strcasecmp($name, 'Content-Type') == 0)) {
                continue;
            }
            $response = $response->withHeader($name, $values);
        }
        if (! $bodyless) {
            $renderer = $this->rendererFactory->createRenderer(new RenderInput(
                path: $route->normalizedPath,
                contentType: $contentType,
                data: $responseData,
                shared: fn (): array => $this->shares->resolve($context, $request),
            ));
            $response->getBody()->write($renderer->render());
        }
        return $response;
    }

    /**
     * Whether the given status code forbids a response body per HTTP semantics: informational (1xx),
     * `204 No Content` and `304 Not Modified`.
     */
    private static function isBodylessStatus(int $statusCode): bool
    {
        return (($statusCode >= 100) && ($statusCode < 200))
            || ($statusCode === 204)
            || ($statusCode === 304);
    }
}
