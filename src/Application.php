<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use SubstancePHP\Container\Container;

class Application implements ContainerInterface
{
    private ContainerInterface $container;
    private RequestHandlerRunnerInterface $runner;

    /**
     * @param array<string, mixed> $env
     * @param class-string<ProviderInterface>[] $providers
     * @param class-string<MiddlewareInterface>[] $middlewares
     */
    public function __construct(
        array $env,
        string $actionRoot,
        array $providers,
        array $middlewares,
    ) {
        $environment = new Environment($env);
        $factorySets = \array_map(fn ($provider) => $provider::factories($environment), $providers);
        $factories = \array_merge(...$factorySets);
        $factories['substance.action-root'] = fn () => $actionRoot;
        $this->container = Container::from($factories);
        try {
            $handler = RequestHandler::from(\array_map($this->get(...), $middlewares));
            $emitter = $this->get(EmitterInterface::class);
            $serverRequestFactory = ServerRequestFactory::fromGlobals(...);
            $errorResponseFallbackGenerator = $this->get(ErrorResponseFallbackGeneratorInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new \RuntimeException(message: $e->getMessage(), previous: $e);
        }
        $this->runner = new RequestHandlerRunner(
            handler: $handler,
            emitter: $emitter,
            serverRequestFactory: $serverRequestFactory,
            serverRequestErrorResponseGenerator: $errorResponseFallbackGenerator,
        );
    }

    public function execute(): void
    {
        $this->runner->run();
    }

    #[\Override]
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    #[\Override] public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
