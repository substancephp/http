<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\Container\Container;

/**
 * A "context" is a {@see Container} for one-off use in handling a single request. By implementing this interface,
 * you provide a means for a {@see Container} to be created for the purpose of providing dependencies for handling
 * a given request.
 */
interface ContextFactoryInterface
{
    public function createContext(ContainerInterface $container, ServerRequestInterface $request): Container;
}
