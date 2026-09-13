<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\ConfigurableMiddlewareInterface;
use SubstancePHP\HTTP\Exception\BaseException\InvalidMiddlewareException;
use SubstancePHP\HTTP\MiddlewareConfig;

/** @implements ConfigurableMiddlewareInterface<ConfigurableMiddlewareConfig> */
final readonly class ConfigurableMiddleware implements ConfigurableMiddlewareInterface
{
    public function parseConfig(array $config): ConfigurableMiddlewareConfig
    {
        $rate = $config['rate'] ?? 10;
        if (! \is_int($rate) || ($rate < 1)) {
            throw new InvalidMiddlewareException('"rate" must be a positive int');
        }
        return new ConfigurableMiddlewareConfig(rate: $rate);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $config = MiddlewareConfig::for($request, self::class);
        return $handler->handle($request->withAttribute('configurable middleware rate', $config->rate));
    }
}
