<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Configure;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;

// ConfigurableMiddleware is a valid, configurable middleware, but the test's stack does not register it.
return #[Configure(ConfigurableMiddleware::class, ['rate' => 30])] static function (): mixed {
    return ['data' => []];
};
