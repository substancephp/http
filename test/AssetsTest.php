<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\Assets;
use SubstancePHP\HTTP\Exception\RenderingException\MissingAssetException;
use TestUtil\TestUtil;

#[CoversClass(Assets::class)]
#[CoversMethod(Assets::class, '__construct')]
#[CoversMethod(Assets::class, 'url')]
class AssetsTest extends TestCase
{
    private function makeInstance(?\Closure $tokenResolver = null): Assets
    {
        return new Assets(
            root: TestUtil::getFixtureRoot() . '/asset',
            baseUrl: '/assets',
            tokenResolver: $tokenResolver,
        );
    }

    #[Test]
    public function urlAppendsTheContentHashByDefault(): void
    {
        $this->assertSame(
            '/assets/example.css?v=' . self::contentHash('example.css'),
            $this->makeInstance()->url('example.css'),
        );
    }

    #[Test]
    public function baseUrlAndPathSlashesAreNormalised(): void
    {
        $assets = new Assets(root: TestUtil::getFixtureRoot() . '/asset', baseUrl: '/assets/');
        $this->assertStringStartsWith('/assets/example.css?v=', $assets->url('/example.css'));
    }

    #[Test]
    public function urlUsesTheTokenResolverWhenItReturnsAToken(): void
    {
        $assets = $this->makeInstance(fn (string $path): string => 'v1');
        $this->assertSame('/assets/example.css?v=v1', $assets->url('example.css'));
    }

    #[Test]
    public function urlFallsBackToTheContentHashWhenTheResolverReturnsNull(): void
    {
        $assets = $this->makeInstance(fn (string $path): ?string => null);
        $this->assertSame(
            '/assets/example.css?v=' . self::contentHash('example.css'),
            $assets->url('example.css'),
        );
    }

    #[Test]
    public function urlPassesThePathToTheTokenResolver(): void
    {
        $seen = null;
        $assets = $this->makeInstance(function (string $path) use (&$seen): string {
            $seen = $path;
            return 'x';
        });

        $assets->url('css/app.css');

        $this->assertSame('css/app.css', $seen);
    }

    #[Test]
    public function urlThrowsWhenTheAssetIsMissing(): void
    {
        $this->expectException(MissingAssetException::class);
        $this->expectExceptionMessage('nonexistent.css');
        $this->makeInstance()->url('nonexistent.css');
    }

    #[Test]
    public function urlDoesNotTouchTheFilesystemWhenTheResolverAnswers(): void
    {
        // A resolver that answers for a non-existent asset means no file is read, so no exception is raised.
        $assets = $this->makeInstance(fn (string $path): string => 'v1');
        $this->assertSame('/assets/nonexistent.css?v=v1', $assets->url('nonexistent.css'));
    }

    private static function contentHash(string $path): string
    {
        $hash = \md5_file(TestUtil::getFixtureRoot() . '/asset/' . $path);
        \assert(\is_string($hash));
        return \substr($hash, 0, 8);
    }
}
