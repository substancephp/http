<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

class Respond
{
    public function __construct(
        public int $statusCode,
    ) {
    }

    public function __invoke(int $statusCode, mixed $data = null): mixed
    {
        $this->statusCode = $statusCode;
        return $data;
    }
}
