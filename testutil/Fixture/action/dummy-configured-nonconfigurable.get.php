<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Configure;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;

// ExampleMiddlewareA is a registered middleware, but not a ConfigurableMiddlewareInterface.
return #[Configure(ExampleMiddlewareA::class, ['rate' => 30])] static function (): mixed {
    return ['data' => []];
};
