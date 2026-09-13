<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Middleware\Skip;

return #[Skip('No\Such\Middleware')] static function (): mixed {
    return ['data' => []];
};
