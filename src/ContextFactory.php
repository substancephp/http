<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\RequestParams\BodyParams;
use SubstancePHP\HTTP\RequestParams\QueryParams;
use SubstancePHP\HTTP\RequestParams\ServerParams;

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
            QueryParams::class => fn () => QueryParams::fromRequest($request),
            BodyParams::class => fn () => BodyParams::fromRequest($request),
            ServerParams::class => fn () => ServerParams::fromRequest($request),
            Respond::class => fn ($c) => new Respond(
                statusCode: $request->getMethod() === 'POST' ? 201 : 200,
                contentType: $c->get('substance.http.default-content-type'),
            ),
        ];
    }
}
