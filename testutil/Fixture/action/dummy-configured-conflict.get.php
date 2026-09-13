<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Configure;
use SubstancePHP\HTTP\Middleware\Skip;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;

return
    #[Skip(ConfigurableMiddleware::class)]
    #[Configure(ConfigurableMiddleware::class, ['rate' => 30])]
    static function (): mixed {
        return ['data' => []];
    };
