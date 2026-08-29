# CHANGELOG

### v0.5.0

* Add `ExceptionHandlerMiddleware`, converting a thrown `UserError` into a useful error response of
  the appropriate content type; and any other exception into a logged 500.
* Every response now carries an `X-Request-Id` correlation ID.
* HTML error responses render through `{templateRoot}/error/{statusCode}.html.php` when present,
  falling back to `{templateRoot}/error.html.php`. The template name is configurable via
  `substance.error-template`.
* `SubstanceProvider` now provides a default `substance.http.default-content-type` of
  `text/html; charset=utf-8`, overridable by later providers.

### v0.4.1

* xdebug now only required in development

### v0.4.0

* Support path parameters e.g. `/foobars/[1].get.php`.

### v0.3.1

* Add short aliases for the HtmlRenderer escaping methods: `->a()`, `->j()`, `->c()` and `->u()`
  for `->escapeHtmlAttr()`, `->escapeJs()`, `->escapeCss()` and `->escapeUrl()` respectively.

### v0.3.0

Major:
* Add functions in HtmlRenderer for escaping not just HTML, but also HTML attributes, JavaScript
  and CSS.
* HtmlRenderer::e() is now deprecated; replaced with ->h() and other context-specialized escaping methods.
* Add a "do-nothing" function in HtmlRenderer to explicitly output the passed content unescaped.
* Add a PHPStan extension for detecting unescaped output in HTML templates.

### v0.2.0

Major:
* Handle HTML, not just JSON

### v0.1.0

Major:
* Abolish 'Out' abstraction. Instead, return the response data directly.
* Introduce invokable 'Respond' class for adjusting status code.
* Abolish 'Status' abstraction. Just use the status integers directly.

### v0.0.19

Major:
* Add SubstanceProvider
* Renaming, restructuring namespaces slightly

### v0.0.18

Major:
* Add Application, Emitter, Environment and Provider abstractions

Minor:
* Change `composer check` to `composer qa` command to avoid name clash
* Upgrade CI to run PHP 8.4

### v0.0.17

* Allow QueryParams, BodyParams and ServerParams classes to accept raw arrays in constructor, to
  make it easier to mock these if required.

### v0.0.16, v0.0.15, v0.0.14, v0.0.13, v0.0.12

* Dependency version upgrades

### v0.0.11

* Add JSON utility, and ordinary constructors for BodyParams, QueryParams and ServerParams

### v0.0.10

* Fix BodyParserMiddleware

### v0.0.9

* Add ContextFactory
* Add BodyParams, QueryParams, ServerParams classes, injectable via ContextFactory per request
* Add BodyParserMiddleware, and RequestUtil

### v0.0.8

* Routing mechanism

### v0.0.7

* Simplify middleware skipping mechanism
  * Abolish SkippableMiddleware base class
  * Any middleware is now skippable if annotated with Skip and handled via RequestHandler

### v0.0.6

* Make RequestHandler immutable

### v0.0.5

* Add "SkippableMiddleware" class
* Move exceptions thrown by this library under a common "BaseException" class

### v0.0.4

* Add "Route" class
* Add "Skip" attribute for marking which middlewares to
  skip on route handlers

### v0.0.3

* Simplify request handler implementation
* Minor documentation improvement

### v0.0.2

* PSR-15 request handler implementation

### v0.0.1

* Initial release, containing "Out" and "Status".
