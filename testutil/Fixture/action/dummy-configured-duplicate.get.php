<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Configure;
use TestUtil\Fixture\Middleware\ConfigurableMiddleware;

return
    #[Configure(ConfigurableMiddleware::class, ['rate' => 30])]
    #[Configure(ConfigurableMiddleware::class, ['rate' => 40])]
    static function (): mixed {
        return ['data' => []];
    };
