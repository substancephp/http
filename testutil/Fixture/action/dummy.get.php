<?php

declare(strict_types=1);

use SubstancePHP\Container\Inject;
use SubstancePHP\HTTP\Middleware\Skip;
use SubstancePHP\HTTP\Out;
use TestUtil\Fixture\Middleware\ExampleMiddlewareA;
use TestUtil\Fixture\Middleware\ExampleMiddlewareC;

return #[Skip(ExampleMiddlewareA::class, ExampleMiddlewareC::class)] static function (
    #[Inject('greetWith')] string $word,
): Out {
    return Out::data(['greeting' => $word]);
};
