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
use SubstancePHP\HTTP\Respond;
use SubstancePHP\HTTP\Route;

/**
 * This middleware assumes there is a {@see Route} stored on the request it is processing. It uses the information
 * in the {@see Route} to handle the "meat" of the request. This will typically involve running the route's
 * callback, converting its return value into an HTTP response, and returning the latter.
 */
readonly class RouteActorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ContextFactoryInterface $contextFactory,
        private RendererFactoryInterface $rendererFactory,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RoutingException if there is no {@see Route} stored on the request.
     * @throws \JsonException if the content returned by the route's action, cannot be JSON-encoded.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(Route::class);
        if (! ($route instanceof Route)) {
            throw new RoutingException('Route attribute not an instance of ' . Route::class);
        }
        $context = $this->contextFactory->createContext($this->container, $request);
        $responseData = $route->execute($context);

        /** @var Respond $respond */
        $respond = $context->get(Respond::class);
        $statusCode = $respond->statusCode;
        $contentType = $respond->contentType;

        if ($statusCode == 204) {
            return $this->responseFactory->createResponse($statusCode);
        }

        $response = $this->responseFactory->createResponse($statusCode)->withHeader('Content-Type', $contentType);
        $renderer = $this->rendererFactory->createRenderer($route->normalizedPath, $contentType, $responseData);
        $responseContent = $renderer->render();
        $response->getBody()->write($responseContent);
        return $response;
    }
}
