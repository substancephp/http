<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Configure;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;

return #[Configure(ConfigurableMiddleware::class, ['rate' => 30])] static function (): mixed {
    return ['data' => []];
};
