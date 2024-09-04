<?php

declare(strict_types=1);

use SubstancePHP\Container\Inject;
use SubstancePHP\HTTP\Middleware\Skip;
use SubstancePHP\HTTP\Out;
use TestUtil\Fixture\Middleware\ExampleNonSkippableMiddleware;
use TestUtil\Fixture\Middleware\ExampleSkippableMiddlewareA;

return #[Skip(ExampleNonSkippableMiddleware::class, ExampleSkippableMiddlewareA::class)] function (
    #[Inject('greetWith')] string $word,
): Out {
    return Out::data(['greeting' => $word]);
};
