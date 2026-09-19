<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

use SubstancePHP\HTTP\Exception\RenderingException\MissingAssetException;

/**
 * Builds cache-busted URLs for an application's static assets.
 *
 * By default a URL carries a token derived from the asset's content hash, so the URL changes if and only if
 * the file's bytes do. An optional {@see self::$tokenResolver} can override that per path (e.g. a deploy-wide
 * version, or a build manifest); returning null from it falls back to the content hash. Hashes are computed
 * lazily and memoised for the life of the instance, and never for a path the resolver answers.
 */
final class Assets
{
    /** @var array<string, string> memoised content hashes, keyed by asset path */
    private array $hashes = [];

    /**
     * @param string $root the directory the assets are served from (read only when hashing)
     * @param string $baseUrl the URL the assets are served under (e.g. `/assets`, or a CDN origin)
     * @param ?\Closure(string $path): ?string $tokenResolver an optional per-path token; return null to fall
     *   back to the content hash
     */
    public function __construct(
        private string $root,
        private string $baseUrl = '/',
        private ?\Closure $tokenResolver = null,
    ) {
    }

    /**
     * The cache-busted URL of the asset at the given path, relative to {@see self::$root}.
     *
     * @throws MissingAssetException if the asset file does not exist
     */
    public function url(string $path): string
    {
        $url = \rtrim($this->baseUrl, '/') . '/' . \ltrim($path, '/');
        $token = $this->token($path);
        return $token === '' ? $url : "{$url}?v={$token}";
    }

    /** @throws MissingAssetException */
    private function token(string $path): string
    {
        if ($this->tokenResolver !== null) {
            $token = ($this->tokenResolver)($path);
            if ($token !== null) {
                return $token;
            }
        }
        return $this->hashes[$path] ??= $this->hash($path);
    }

    /** @throws MissingAssetException */
    private function hash(string $path): string
    {
        $file = \rtrim($this->root, '/') . '/' . \ltrim($path, '/');
        $hash = \is_file($file) ? \md5_file($file) : false;
        if ($hash === false) {
            throw new MissingAssetException($file);
        }
        return \substr($hash, 0, 8);
    }
}
