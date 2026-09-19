# Responses and redirects

An action returns the response body; the status code and headers are set on the injected `Respond`.

## Return data

```php
// server/http/foo.get.php

return static function (): mixed {
    return ['id' => 42];
};
```

Return the data directly for the default response (status `200`, or `201` for `POST`). Call
`$respond(...)` only to set another status code:

```php
use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    return $respond(422, ['message' => 'Invalid request body']);
};
```

## Set headers

```php
$respond->setHeader('Cache-Control', 'no-store');       // replace
$respond->setHeader('Set-Cookie', ['a=1', 'b=2']);      // multiple values
$respond->removeHeader('Vary');                         // remove
```

Header names are case-insensitive.

## Choose the content type

```php
use SubstancePHP\HTTP\RequestParams\PathParams;
use SubstancePHP\HTTP\Respond;

return static function (PathParams $params, Respond $respond): mixed {
    $respond->setHeader('Content-Type', 'text/html');
    return $respond(200, ['id' => $params['id']]);
};
```

The default is `substance.http.default-content-type`. `application/json*` and `text/html*` have
renderers; any other content type throws `UnsupportedContentTypeException`.

## Choose the template

An HTML body is rendered with the template for the route's normalized path by default. Select another with
`setTemplate()`, relative to the template root and without the `.html.php` suffix:

```php
use SubstancePHP\HTTP\Respond;

return static function (Respond $respond): mixed {
    $respond->setTemplate('stores/detail');   // renders {templateRoot}/stores/detail.html.php
    return ['id' => 42];
};
```

`removeTemplate()` reverts to the route's path. For non-HTML content types the template is ignored.

## Return no body

```php
return $respond(204);
```

A `1xx`/`204`/`304` status is bodyless on its own; at any other status, remove the `Content-Type`
(`$respond->removeHeader('Content-Type')`) to omit the body.

## Redirect

```php
return static function (Respond $respond): mixed {
    return $respond->redirectTo('/stores/new');            // 303
    // return $respond->redirectTo('/stores/new', 308);    // preserve method
};
```

## Header precedence

Headers are layered, and each layer overrides the ones set before it:

1. **Global defaults**: e.g. the configured default content type.
2. **The specific route action**: headers set on `Respond`, overriding the defaults.
3. **Middleware and the framework**: may override the action (e.g. the `X-Request-Id` correlation id).

So an action's headers win over the defaults, but outward middleware can still override them. If an
action throws, its headers are dropped and the error response is built from scratch.
