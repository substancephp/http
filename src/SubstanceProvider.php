<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\Middleware\BodyParserMiddleware;
use SubstancePHP\HTTP\Middleware\ExceptionHandlerMiddleware;
use SubstancePHP\HTTP\Middleware\MethodNormalizerMiddleware;
use SubstancePHP\HTTP\Middleware\RouteActorMiddleware;
use SubstancePHP\HTTP\Middleware\RouteMatcherMiddleware;

/** A provider for dependencies common to all SubstancePHP\HTTP applications. */
abstract class SubstanceProvider implements ProviderInterface
{
    #[\Override]
    /** @inheritdoc */
    public static function factories(EnvironmentInterface $environment): array
    {
        return [
            // environment
            EnvironmentInterface::class => fn () => $environment,
            ContextFactoryInterface::class => fn () => new ContextFactory(),

            // configuration
            'substance.http.default-content-type' => fn () => 'text/html; charset=utf-8',

            // http response generation
            EmitterInterface::class => fn () => new Emitter(),
            ErrorResponseFallbackGeneratorInterface::class => fn ($c) => new ErrorResponseFallbackGenerator(
                $c->get(ResponseFactoryInterface::class),
                $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null,
            ),
            ResponseFactoryInterface::class => fn () => new ResponseFactory(),
            RendererFactoryInterface::class => fn ($c) => new RendererFactory(
                templateRoot: $c->get('substance.template-root'),
                htmlEncoding: $c->get('substance.html-encoding'),
            ),

            // middleware
            BodyParserMiddleware::class => fn () => new BodyParserMiddleware(),
            ExceptionHandlerMiddleware::class => fn ($c) => new ExceptionHandlerMiddleware(
                $c->get(ResponseFactoryInterface::class),
                $c->get(ErrorResponseFallbackGeneratorInterface::class),
                $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null,
            ),
            MethodNormalizerMiddleware::class => fn () => new MethodNormalizerMiddleware(),
            RouteActorMiddleware::class => fn ($c) => new RouteActorMiddleware(
                $c,
                $c->get(ContextFactoryInterface::class),
                $c->get(RendererFactoryInterface::class),
                $c->get(ResponseFactoryInterface::class),
            ),
            RouteMatcherMiddleware::class => Container::autowire(...),
        ];
    }
}
