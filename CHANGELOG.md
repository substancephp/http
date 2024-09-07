# CHANGELOG

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
