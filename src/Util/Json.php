<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Util;

final class Json
{
    /**
     * @return string a JSON representation of the pass content
     * @throws \JsonException if content cannot be JSON-encoded
     */
    public static function of(mixed $content, bool $serializeEmptyAsObject = true): string
    {
        if (\is_array($content) && \count($content) == 0 && $serializeEmptyAsObject) {
            return \json_encode(value: $content, flags: \JSON_FORCE_OBJECT | \JSON_THROW_ON_ERROR);
        }
        return \json_encode(value: $content, flags: \JSON_THROW_ON_ERROR);
    }
}
