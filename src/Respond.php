<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * A mutable, request-scoped accumulator of the response "intent" an action wishes to express: the
 * status code, any response headers, and the template to render for an HTML body. The response body is
 * whatever the action returns.
 *
 * An action typically receives a {@see Respond} via dependency injection and either returns its data
 * directly, or calls it (e.g. <code>return $respond(422, ['message' => 'Invalid']);</code>) to set the
 * status code while returning the data. Response headers are set with {@see self::setHeader()} /
 * {@see self::removeHeader()}, or via sugar such as {@see self::redirectTo()}.
 *
 * The absence of a `Content-Type` header means the response has no body: no renderer runs and no
 * `Content-Type` header is emitted. This is how redirects and other bodyless responses are expressed,
 * and it also means the `Content-Type` header doubles as the signal that selects which renderer turns
 * the returned data into the response body.
 *
 * An HTML body is rendered with the template for the route's normalized path unless the action selects
 * another with {@see self::setTemplate()}.
 *
 * Header names are case-insensitive, and a header may carry multiple values (e.g. `Set-Cookie`).
 */
class Respond
{
    private int $statusCode;

    /**
     * @var array<string, array{name: string, values: string[]}> response headers, keyed by
     *   lowercased name; each records its original spelling and its (possibly multiple) values
     */
    private array $headers = [];

    /** The template rendered for an HTML body, relative to the template root and without the suffix. */
    private ?string $template = null;

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Sets the status code, then returns the passed data so that an action can return it directly.
     *
     * The `T` template keeps the data's type through the call, so `return $respond(422, $data);` analyses the
     * same as `return $data;` would. Removing it would erase the data's shape at every call site.
     *
     * @template T
     * @param T $data the response data, returned unchanged
     * @return T
     */
    public function __invoke(int $statusCode, mixed $data = null): mixed
    {
        $this->statusCode = $statusCode;
        return $data;
    }

    /** The status code of the response. */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets the named header, replacing any values already set for it.
     *
     * @param string|string[] $value
     */
    public function setHeader(string $name, string|array $value): void
    {
        $this->headers[\strtolower($name)] = ['name' => $name, 'values' => \array_values((array) $value)];
    }

    /** Removes the named header, if set. */
    public function removeHeader(string $name): void
    {
        unset($this->headers[\strtolower($name)]);
    }

    /** @return string the values of the named header joined with ', '; an empty string if it is not set. */
    public function getHeaderLine(string $name): string
    {
        $values = $this->headers[\strtolower($name)]['values'] ?? [];
        return \implode(', ', $values);
    }

    /** @return array<string, string[]> all headers, keyed by their (original-cased) name */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            $headers[$header['name']] = $header['values'];
        }
        return $headers;
    }

    /**
     * Selects the template rendered for an HTML body, relative to the template root and without the
     * `.html.php` suffix: <code>setTemplate('stores/detail')</code> renders
     * <code>{templateRoot}/stores/detail.html.php</code>. By default the route's normalized path is used.
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    /** The template selected by {@see self::setTemplate()}, or null to use the route's normalized path. */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /** Removes any template selected by {@see self::setTemplate()}, reverting to the route's normalized path. */
    public function removeTemplate(): void
    {
        $this->template = null;
    }

    /**
     * Turns the response into a redirect to the given location: sets the status code and the
     * `Location` header, and removes any `Content-Type` so that no body is rendered. Returns null so
     * that an action can simply <code>return $respond->redirectTo('/foo');</code>.
     */
    public function redirectTo(string $location, int $statusCode = 303): null
    {
        $this->statusCode = $statusCode;
        $this->setHeader('Location', $location);
        $this->removeHeader('Content-Type');
        return null;
    }
}
