<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Respond;

return static function (Respond $respond, string $template): mixed {
    $respond->setTemplate($template);
    return ['title' => 'Dynamic'];
};
