# CHANGELOG

### Unreleased

Major (breaking):
* Shared template data is a single `ShareInterface` implementation whose public properties are the template
  variables, instead of a list of `ShareInterface` services returning arrays. `ShareInterface` is now a
  marker, `Templating::$share` takes that class name, and `HtmlRenderer` and `RenderInput` take the share
  object rather than an array. `Shares` becomes `Share`, which resolves that one class and autowires it when
  the application binds nothing, and `EmptyShare` is the default. See `docs/templating.md`.

Minor:
* The PHPStan extension also checks that the variables a template declares are provided by the action it
  renders for, or by the application's share, and that the types they are provided with satisfy what the
  template declares. It checks what `partial()`, `layout()` and `beginElement()` pass as well.
  `#[DefaultTemplate]` and `#[AltTemplates]` declare templates an action renders off the conventional path,
  and the declared set is enforced: the declared default renders and a selection outside it throws.
  See `docs/static-analysis.md`.
* `Respond::__invoke()` is generic, so the data an action passes through it keeps its type:
  `return $respond(200, $data);` analyses exactly as `return $data;` does. Templates' `@var` blocks are
  documented as the contract the rules read. See `docs/static-analysis.md`.

### v0.11.0

* An action can render a template other than the one for its route by calling `Respond::setTemplate()`;
  `removeTemplate()` reverts to the default. See `docs/responses.md`.
* Add `$this->asset()`, a template helper returning cache-busted URLs for static assets, configured via
  `Templating::$assets`. The token is the asset's content hash by default, overridable per path with a
  `tokenResolver`. See `docs/templating.md`.

### v0.10.0

Major (breaking):
* `Application::make()` now takes a single `Templating` (the template root, the source encoding, the default
  layout, and the shared template variables) instead of the `templateRoot`/`htmlEncoding` arguments. The
  `substance.template-root`, `substance.html-encoding` and `substance.default-layout` container keys are
  gone; that configuration now lives on `Templating`.
* `RendererFactoryInterface::createRenderer()` now takes a single `RenderInput` (the renderer path, the
  response content type, the action's data, and the shared variables) instead of three positional arguments.
* Shared template data is contributed by `ShareInterface` services listed in `Templating::$shared`, instead of
  an array of values/closures; `RouteActorMiddleware` and `ExceptionHandlerMiddleware` take a `Shares`.
* `HtmlRenderer` takes a `shared` array; `ExceptionHandlerMiddleware` now requires the application container
  and a `ContextFactoryInterface`.

Minor:
* Applications can expose shared, cross-cutting data (CSRF fields, navigation state, the current user, flash
  messages) to every template as plain variables. Implement a `ShareInterface`, register it as a container
  service, and list its class in `Templating::$shared`. Shared variables are resolved only when an HTML
  template is rendered, so responses of other content types (e.g. JSON) pay nothing for them. See
  `docs/templating.md`.

### v0.9.0

Major (breaking):
* `#[Skip]` now takes a single middleware (repeat the attribute to skip several), and its public
  property is `middleware` (a `string`) rather than `skippableMiddlewares` (a `string[]`).

Minor:
* Middleware can be registered but disabled by default: pass a `MiddlewareSpec::disable()` in place of
  a bare class name in `Application::make()`'s middleware list (e.g.
  `[A::class, MiddlewareSpec::disable(B::class), C::class]`). A route opts it back in with `#[Engage]`,
  the inverse of `#[Skip]`.
* Middleware can be configured per route. A middleware that implements
  `ConfigurableMiddlewareInterface` receives a route's config, declared with `#[Configure()]` (e.g.
  `#[Configure(RateLimiterMiddleware::class, ['rate' => 30])]`). Shared middleware instances are kept
  and the config is parsed once per request. See `docs/middleware.md`.
* Inconsistent middleware declarations now throw `InvalidMiddlewareException`: the same middleware
  declared twice, or both skipped and engaged/configured; a route referencing a middleware that is not
  registered; a route configuring a middleware that is not configurable; or the same middleware
  registered twice in the stack.

#### Upgrading to 0.9.0

**`#[Skip]` arity.** `#[Skip]` now takes a single middleware; repeat it to skip several:

```php
// before
#[Skip(A::class, B::class)]

// after
#[Skip(A::class)] #[Skip(B::class)]
```

### v0.8.0

Major (breaking):
* `Respond` now represents a response as a status code plus a set of response headers, rather than a
  status code plus a single content type. Actions configure headers with `Respond::setHeader()` and
  `Respond::removeHeader()`, or use `Respond::redirectTo()` to issue a redirect.
* `Respond::__invoke()` no longer takes a content type: it is now
  `__invoke(int $statusCode, mixed $data = null)`, returning the passed data. Set the content type as
  a header instead.
* `Respond::__construct()` no longer takes a content type; it takes only `int $statusCode`.
* The content type is now carried as the `Content-Type` header and still selects the renderer that
  turns the action's return value into the body. A response is bodyless when its status code forbids a
  body (`1xx`, `204`, `304`) or when the action removes the `Content-Type` header.
* The `EmptyRenderer` is retired; a content type with no renderer now throws
  `UnsupportedContentTypeException`.

#### Upgrading to 0.8.0

An action that only did `return $respond($status, $data)` needs no changes. The steps below cover the
code that did more than that.

**1. Setting the content type.** `Respond::__invoke()` no longer accepts a content type as a third
argument. Set the `Content-Type` header instead:

```php
// before
return $respond(200, $data, 'application/json');

// after
$respond->setHeader('Content-Type', 'application/json');
return $respond(200, $data);
```

**2. Constructing `Respond` directly.** The constructor now takes only the status code:

```php
// before
$respond = new Respond(200, 'application/json');

// after
$respond = new Respond(200);
$respond->setHeader('Content-Type', 'application/json');
```

**3. Reading `Respond`'s state.** `$statusCode` and `$contentType` are no longer public. Use the
accessors instead:

```php
// before
$status = $respond->statusCode;
$type = $respond->contentType;

// after
$status = $respond->getStatusCode();
$type = $respond->getHeaderLine('Content-Type');
```

**4. Unsupported content types now throw.** Previously any content type other than `application/json*`
and `text/html*` produced an empty body (via the removed `EmptyRenderer`). `RendererFactory` now throws
`SubstancePHP\HTTP\Exception\RenderingException\UnsupportedContentTypeException`. If you relied on that
empty body, use a supported content type, or express "no body" explicitly by removing the `Content-Type`
header (`$respond->removeHeader('Content-Type')`) or by returning a bodyless status (`1xx`, `204`,
`304`).

The `substance.http.default-content-type` container key is unchanged; it now seeds the default
`Content-Type` header.

New in 0.8.0: set arbitrary response headers with `setHeader()` / `removeHeader()` (pass an array for a
multi-valued header such as `Set-Cookie`), and redirect with `return $respond->redirectTo('/path');`.

### v0.7.1

* Dependency version upgrades.

### v0.7.0

* `HtmlRenderer` now supports slots and custom elements.

### v0.6.0

* `HtmlRenderer` now supports layouts and partials.

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
