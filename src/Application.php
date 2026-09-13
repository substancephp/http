<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;

class Application implements ContainerInterface
{
    private function __construct(
        private ContainerInterface $container,
        private RequestHandlerRunnerInterface $runner,
    ) {
    }

    /**
     * @param array<string, mixed> $env
     * @param class-string<ProviderInterface>[] $providers
     * @param array<class-string<MiddlewareInterface>|MiddlewareSpec> $middlewares listed OUTER to INNER;
     *   a bare class name is enabled by default, or wrap it in {@see MiddlewareSpec::disable()} to skip
     *   it unless a route opts it back in with {@see \SubstancePHP\HTTP\Middleware\Engage}.
     * @throws InvalidMiddlewareException if the same middleware is registered more than once.
     */
    public static function make(
        array $env,
        string $actionRoot,
        string $templateRoot,
        array $providers,
        array $middlewares,
        string $htmlEncoding,
    ): self {
        // TODO Consider specialising application constructors for JSON-only or HTML-only.
        $environment = new Environment($env);
        $factorySets = \array_map(fn ($provider) => $provider::factories($environment), $providers);
        $factories = \array_merge(...$factorySets);
        $factories['substance.action-root'] = fn () => $actionRoot;
        $factories['substance.template-root'] = fn () => $templateRoot;
        $factories['substance.html-encoding'] = fn () => $htmlEncoding;
        $container = Container::from($factories);

        $classes = [];
        $skippedByDefault = [];
        foreach ($middlewares as $middleware) {
            $spec = ($middleware instanceof MiddlewareSpec) ? $middleware : MiddlewareSpec::enable($middleware);
            if (\in_array($spec->class, $classes, true)) {
                throw new InvalidMiddlewareException("Middleware registered more than once: {$spec->class}");
            }
            $classes[] = $spec->class;
            if (! $spec->enabledByDefault) {
                $skippedByDefault[] = $spec->class;
            }
        }

        $handler = RequestHandler::from(\array_map($container->get(...), $classes), $skippedByDefault);
        $emitter = $container->get(EmitterInterface::class);
        $serverRequestFactory = ServerRequestFactory::fromGlobals(...);
        $errorResponseFallbackGenerator = $container->get(ErrorResponseFallbackGeneratorInterface::class);

        $runner = new RequestHandlerRunner(
            handler: $handler,
            emitter: $emitter,
            serverRequestFactory: $serverRequestFactory,
            serverRequestErrorResponseGenerator: $errorResponseFallbackGenerator,
        );

        return new self($container, $runner);
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
