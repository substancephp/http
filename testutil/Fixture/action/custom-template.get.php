<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    $respond->setTemplate('custom-template-alt');
    return ['name' => 'World'];
};
