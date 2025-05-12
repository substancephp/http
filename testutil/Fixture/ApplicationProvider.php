<?php

declare(strict_types=1);

namespace TestUtil\Fixture;

use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\EnvironmentInterface;
use SubstancePHP\HTTP\ProviderInterface;
use TestUtil\Fixture\Middleware\AttributeGatheringMiddleware;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;

class ApplicationProvider implements ProviderInterface
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public static function factories(EnvironmentInterface $environment): array
    {
        return [
            'foob.ar' => fn () => 30,
            'barb.az' => fn () => 'word',

            AttributeGatheringMiddleware::class => Container::autowire(...),
            ExampleMiddlewareA::class => Container::autowire(...),
            ExampleMiddlewareB::class => Container::autowire(...),
            ExampleMiddlewareC::class => Container::autowire(...),
        ];
    }
}
