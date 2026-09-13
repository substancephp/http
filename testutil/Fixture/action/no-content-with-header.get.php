<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    $respond->setHeader('Cache-Control', 'no-store');
    return $respond(204);
};
