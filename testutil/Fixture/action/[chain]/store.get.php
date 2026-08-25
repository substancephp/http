<?php

declare(strict_types=1);

use SubstancePHP\HTTP\RequestParams\PathParams;

return static function (PathParams $params): mixed {
    return ['data' => ['chain' => $params['chain']]];
};
