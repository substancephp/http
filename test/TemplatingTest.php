<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Templating;

#[CoversClass(Templating::class)]
class TemplatingTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $templating = new Templating('/templates');
        $this->assertSame('/templates', $templating->root);
        $this->assertSame('utf-8', $templating->encoding);
        $this->assertSame('layout', $templating->defaultLayout);
        $this->assertSame([], $templating->shared);
        $this->assertNull($templating->assets);
    }
}
