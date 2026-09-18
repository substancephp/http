<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The ordered set of {@see ShareInterface} services to resolve into shared template variables.
 *
 * Each share is resolved from the request-scoped container by its class id. Where two shares contribute the
 * same variable name, the later one wins; action data still wins over all of them.
 */
final readonly class Shares
{
    /** @param list<class-string<ShareInterface>> $shareClasses */
    public function __construct(private array $shareClasses)
    {
    }

    /**
     * @return array<string, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(ContainerInterface $context, ServerRequestInterface $request): array
    {
        $resolved = [];
        foreach ($this->shareClasses as $class) {
            /** @var ShareInterface $share */
            $share = $context->get($class);
            $resolved = \array_merge($resolved, $share($context, $request));
        }
        return $resolved;
    }
}
