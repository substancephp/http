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

    /** @return array<string, \Closure(ContainerInterface $c): mixed> */
    protected function createFactories(ServerRequestInterface $request): array
    {
        return [
            ServerRequestInterface::class => fn () => $request,
            QueryParams::class => fn () => QueryParams::from($request),
            BodyParams::class => fn () => BodyParams::from($request),
            ServerParams::class => fn () => ServerParams::from($request),
        ];
    }
}
