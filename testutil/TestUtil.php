<?php

declare(strict_types=1);

namespace TestUtil;

abstract class TestUtil
{
    public static function getFixtureRoot(): string
    {
        return __DIR__ . '/Fixture';
    }

    public static function getActionFixtureRoot(): string
    {
        return self::getFixtureRoot() . '/action';
    }
}
