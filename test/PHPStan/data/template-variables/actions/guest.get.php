<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond, bool $signedIn): mixed {
    if ($signedIn) {
        return $respond->redirectTo('/login', 302);
    }

    return ['name' => 'Corner Shop'];
};
