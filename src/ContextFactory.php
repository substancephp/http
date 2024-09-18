<?php

namespace SubstancePHP\HTTP;

use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\RequestParams\BodyParams;
use SubstancePHP\HTTP\RequestParams\Headers;
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\RequestParams\ServerParams;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContextFactory implements ContextFactoryInterface
{
    final public function createContext(ContainerInterface $container, ServerRequestInterface $request): Container
    {
        $factories = $this->createFactories($request);
        return Container::extend($container, $factories);
    }

    /** @return array<string, \Closure(Container $c, string $id): mixed> */
    protected function createFactories(ServerRequestInterface $request): array
    {
        return [
            ServerRequestInterface::class => fn () => $request,
            QueryParams::class => fn () => new QueryParams($request),
            BodyParams::class => fn () => new BodyParams($request),
            ServerParams::class => fn () => new ServerParams($request),
        ];
    }
}
