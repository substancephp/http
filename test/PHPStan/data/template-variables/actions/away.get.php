<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    return $respond->redirectTo('/login');
};
