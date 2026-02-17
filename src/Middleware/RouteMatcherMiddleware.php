<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\Container\Inject;
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Route;
use SubstancePHP\HTTP\Util\Request;

readonly class RouteMatcherMiddleware implements MiddlewareInterface
{
    public function __construct(
        #[Inject('substance.action-root')] private string $actionRoot,
    ) {
    }

    /**
     * @throws UserError
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $route = Route::from($this->actionRoot, $method, $path) ?? UserError::throw(404);
        return $handler->handle($request->withAttribute(Route::class, $route));
    }
}
