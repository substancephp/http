<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond) {
    return $respond(422, ['message' => 'Invalid request body']);
};
