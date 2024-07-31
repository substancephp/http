<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Dummy;

// DELETE ME
class DummyTest extends TestCase
{
    public function testOnePlusOne(): void
    {
        $this->assertEquals(2, Dummy::onePlusOne());
    }
}