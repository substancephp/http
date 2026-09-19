<?php

declare(strict_types=1);

use SubstancePHP\HTTP\AltTemplates;

return #[AltTemplates(['catalog-empty'])] static function (): mixed {
    return ['items' => []];
};
