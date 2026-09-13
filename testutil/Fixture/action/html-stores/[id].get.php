<?php

declare(strict_types=1);

use SubstancePHP\HTTP\RequestParams\PathParams;
use SubstancePHP\HTTP\Respond;

return static function (PathParams $params, Respond $respond): mixed {
    $respond->setHeader('Content-Type', 'text/html');
    return $respond(200, ['id' => $params['id']]);
};
