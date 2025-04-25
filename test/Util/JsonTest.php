<?php

declare(strict_types=1);

namespace Test\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Util\Json;

#[CoversClass(Json::class)]
#[CoversMethod(Json::class, 'of')]
class JsonTest extends TestCase
{
    #[Test]
    public function of(): void
    {
        $json = Json::of(content: [], serializeEmptyAsObject: true);
        $this->assertEquals('{}', $json);

        $json = Json::of(content: [], serializeEmptyAsObject: false);
        $this->assertEquals('[]', $json);

        $json = Json::of(content: null, serializeEmptyAsObject: true);
        $this->assertEquals('null', $json);

        $json = Json::of(content: null, serializeEmptyAsObject: false);
        $this->assertEquals('null', $json);

        $this->assertEquals('""', Json::of(content: ''));
    }
}
