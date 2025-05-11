<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Provider;

use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use SubstancePHP\HTTP\ContextFactory\ContextFactory;
use SubstancePHP\HTTP\ContextFactory\ContextFactoryInterface;
use SubstancePHP\HTTP\Emitter\Emitter;
use SubstancePHP\HTTP\Environment\EnvironmentInterface;
use SubstancePHP\HTTP\ErrorResponseFallbackGenerator\ErrorResponseFallbackGenerator;
use SubstancePHP\HTTP\ErrorResponseFallbackGenerator\ErrorResponseFallbackGeneratorInterface;
use SubstancePHP\HTTP\Middleware\BodyParserMiddleware;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;

/**
 * A provider for dependencies common to all SubstancePHP\HTTP applications.
 */
abstract class SubstanceProvider implements ProviderInterface
{
    #[\Override]
    /** @inheritdoc */
    public static function factories(EnvironmentInterface $environment): array
    {
        return [
            // environment
            EnvironmentInterface::class => fn () => $environment,
            ContextFactoryInterface::class => fn ($c) => new ContextFactory(),

            // http response generation
            EmitterInterface::class => fn ($c) => new Emitter(),
            ErrorResponseFallbackGeneratorInterface::class => fn ($c) => new ErrorResponseFallbackGenerator(
                $c->get(ResponseFactoryInterface::class),
                $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null,
            ),
            ResponseFactoryInterface::class => fn ($c) => new ResponseFactory(),

            // middleware
            BodyParserMiddleware::class => fn () => new BodyParserMiddleware(),
            RouteActorMiddleware::class => fn ($c) => new RouteActorMiddleware(
                $c,
                $c->get(ContextFactoryInterface::class),
                $c->get(ResponseFactoryInterface::class),
            ),
            RouteMatcherMiddleware::class => fn ($c) => new RouteMatcherMiddleware($c->get('substance.action-root')),
        ];
    }
}
