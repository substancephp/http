# CHANGELOG

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
