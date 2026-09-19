<?php

declare(strict_types=1);

namespace TestUtil\Fixture;

use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\HTTP\ShareInterface;

/** Contributes fixed copy plus a variable read from the request, for exercising {@see ShareInterface}. */
final readonly class GreetingShare implements ShareInterface
{
    public string $who;

    public function __construct(
        ServerRequestInterface $request,
        public string $appName = 'My App',
    ) {
        $this->who = $request->getHeaderLine('X-Who');
    }
}
