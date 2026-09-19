<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SubstancePHP\Container\Container;

/**
 * The application's {@see ShareInterface}, resolved for a single request.
 *
 * The class is fetched from the request-scoped container by its class id, so an application that binds it as
 * a service gets that instance. An unbound class is autowired from the same container, which means a share
 * whose constructor dependencies are all resolvable (request-scoped services, or parameters with defaults)
 * needs no binding at all.
 */
final readonly class Share
{
    /** @param class-string<ShareInterface> $shareClass */
    public function __construct(private string $shareClass)
    {
    }

    /**
     * The share's public properties are the templates' shared variables (see {@see Templating::$share}).
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(Container $context): ShareInterface
    {
        /** @var ShareInterface $share */
        $share = ($context->has($this->shareClass))
            ? $context->get($this->shareClass)
            : Container::autowire($context, $this->shareClass);
        return $share;
    }
}
