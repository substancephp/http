<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    // Dropping the content type asks for a bodyless response; it must not be an error.
    $respond->removeHeader('Content-Type');
    return $respond(200, ['data' => ['ok' => true]]);
};
