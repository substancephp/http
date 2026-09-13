<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Engage;
use SubstancePHP\HTTP\Middleware\Skip;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;

return #[Skip(ExampleMiddlewareA::class)] #[Engage(ExampleMiddlewareA::class)] static function (): mixed {
    return ['data' => []];
};
