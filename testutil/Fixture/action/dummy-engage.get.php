<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Engage;
use TestUtil\Fixture\Middleware\ExampleMiddlewareB;

return #[Engage(ExampleMiddlewareB::class)] static function (): mixed {
    return ['data' => ['engaged' => true]];
};
