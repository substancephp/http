<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Middleware\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\UnexpectedRequestAttributeValueException;
use SubstancePHP\HTTP\Middleware\Skip;
use SubstancePHP\HTTP\Route;

/**
 * This class should be extended by middleware classes that you want to be skippable using the {@see Skip} attribute.
 */
readonly abstract class SkippableMiddleware implements MiddlewareInterface
{
    /**
     * This method must be implemented in the inheriting middleware class.
     *
     * This method will be called by {@see SkippableMiddleware::process}, unless there is a {@see Route} attribute
     * on the request for which {@see Skip} has been used to skip the middleware.
     */
    abstract protected function doProcess(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface;

    /**
     * @throws \ReflectionException
     * @throws UnexpectedRequestAttributeValueException if there is no request attribute keyed {@see Route::class}
     *   holding an instance of {@see Route}
     */
    final public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(Route::class);
        if (! ($route instanceof Route)) {
            throw new UnexpectedRequestAttributeValueException();
        }
        if ($route->shouldSkip(static::class)) {
            return $handler->handle($request);
        }
        return $this->doProcess($request, $handler);
    }
}
