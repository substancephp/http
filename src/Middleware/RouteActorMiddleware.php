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
use SubstancePHP\HTTP\Exception\BaseException\InvalidActionException;
use SubstancePHP\HTTP\Exception\BaseException\RoutingException;
use SubstancePHP\HTTP\Out;
use SubstancePHP\HTTP\Route;
use SubstancePHP\HTTP\Status;

/**
 * This middleware assumes there is a {@see Route} stored on the request it is processing. It uses the information
 * in the {@see Route} to handle the "meat" of the request. This will typically involve running the route's
 * callback, converting the returned {@see Out} instance into an HTTP response, and returning the latter.
 */
readonly class RouteActorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ContextFactoryInterface $contextFactory,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RoutingException if there is no {@see Route} stored on the request.
     * @throws InvalidActionException if the route's action does not return an {@see Out} instance.
     * @throws \JsonException if the content of the {@see Out} returned by the route's action, cannot be JSON-encoded.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(Route::class);
        if (! ($route instanceof Route)) {
            throw new RoutingException('Route attribute not an instance of ' . Route::class);
        }
        $context = $this->contextFactory->createContext($this->container, $request);
        $out = $route->execute($context);
        if (! ($out instanceof Out)) {
            throw new InvalidActionException('Route action did not return instance of ' . Out::class);
        }
        $status = $out->getStatus();
        $response = $this->responseFactory->createResponse($status->getHTTPCode());
        if ($status === Status::NO_CONTENT) {
            return $response;
        }
        /** @var \ArrayObject<string, mixed> $json */
        $json = new \ArrayObject([]);
        $message = $out->getMessage();
        if ($message) {
            $json['message'] = $message;
        }
        $data = $out->getData();
        if ($data) {
            $json['data'] = $data;
        }
        if (!$data && !$message) {
            $json['message'] = $out->getStatus()->getPhrase();
        }
        $response->getBody()->write(\json_encode($json, \JSON_THROW_ON_ERROR));
        return $response;
    }
}
