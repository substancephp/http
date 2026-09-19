<?php

declare(strict_types=1);

use SubstancePHP\HTTP\AltTemplates;
use SubstancePHP\HTTP\Respond;

return #[AltTemplates(['combined-alt'])] static function (Respond $respond, int $flag): mixed {
    if ($flag == 0) {
        return ['items' => []];
    }

    return ['message' => 'x'];
};
