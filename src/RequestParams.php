<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * @template-extends \ArrayObject<string, mixed>
 */
abstract class RequestParams extends \ArrayObject
{
    /** @param array<string, mixed> $array */
    public function __construct(array $array)
    {
        parent::__construct($array);
    }
}
