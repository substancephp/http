<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Skip;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;

return #[Skip(ExampleMiddlewareA::class)] #[Skip(ExampleMiddlewareA::class)] static function (): mixed {
    return ['data' => []];
};
