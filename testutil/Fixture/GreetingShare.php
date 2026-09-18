<?php

declare(strict_types=1);

namespace TestUtil\Fixture;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\ShareInterface;

/** Contributes fixed copy plus a variable read from the request, for exercising {@see ShareInterface}. */
final readonly class GreetingShare implements ShareInterface
{
    public function __invoke(ContainerInterface $context, ServerRequestInterface $request): array
    {
        return ['appName' => 'My App', 'who' => $request->getHeaderLine('X-Who')];
    }
}
