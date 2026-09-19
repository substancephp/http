<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): array {
    $ignored = static function (int $n): array {
        return ['ignored' => $n];
    };
    $ignored(1);

    return ['name' => 'Corner Shop'];
};
