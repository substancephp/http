<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    $respond->setHeader('X-Single', 'one');
    $respond->setHeader('X-Multi', ['a', 'b']);
    return $respond(200, ['data' => ['ok' => true]]);
};
