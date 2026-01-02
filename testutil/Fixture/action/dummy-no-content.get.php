<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Out;

return static function (): Out {
    return Out::noContent();
};
