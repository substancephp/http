<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Out;

return function (): Out {
    return Out::data(['another route reached' => true]);
};
