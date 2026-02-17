<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

class Respond
{
    public function __construct(
        public int $statusCode,
        public string $contentType,
    ) {
    }

    public function __invoke(
        int $statusCode,
        mixed $data = null,
        ?string $contentType = null,
    ): mixed {
        $this->statusCode = $statusCode;
        if ($contentType !== null) {
            $this->contentType = $contentType;
        }
        return $data;
    }
}
