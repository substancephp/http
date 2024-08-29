<?php

declare(strict_types=1);

use SubstancePHP\Container\Inject;
use SubstancePHP\HTTP\Middleware\Skip;
use SubstancePHP\HTTP\Out;

return #[Skip('hi', 'there')] function (
    #[Inject('greetWith')] string $word,
): Out {
    return Out::data(['greeting' => $word]);
};
