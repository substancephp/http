<?php

declare(strict_types=1);

namespace Test;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\HTTP\EmptyShare;
use SubstancePHP\HTTP\Share;
use TestUtil\Fixture\GreetingShare;

#[CoversClass(Share::class)]
class ShareTest extends TestCase
{
    #[Test]
    public function resolveUsesTheBoundInstance(): void
    {
        $share = new EmptyShare();
        $context = Container::from([EmptyShare::class => fn () => $share]);

        $this->assertSame($share, (new Share(EmptyShare::class))->resolve($context));
    }

    #[Test]
    public function resolveAutowiresAnUnboundShare(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/')
            ->withHeader('X-Who', 'World');
        $context = Container::from([ServerRequestInterface::class => fn () => $request]);

        $share = (new Share(GreetingShare::class))->resolve($context);

        $this->assertInstanceOf(GreetingShare::class, $share);
        $this->assertSame('My App', $share->appName);
        $this->assertSame('World', $share->who);
    }
}
