<?php

declare(strict_types=1);

use SubstancePHP\HTTP\DefaultTemplate;
use SubstancePHP\HTTP\Respond;

return #[DefaultTemplate('declared-default')] static function (Respond $respond): mixed {
    $respond->setHeader('Content-Type', 'text/html');
    return [];
};
