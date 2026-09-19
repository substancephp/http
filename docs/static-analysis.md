# Static analysis

`substancephp/http` bundles a small PHPStan extension that nudges you to
escape the output in `HtmlRenderer` templates, and to give your templates the
variables they declare. It is a best-effort lint, not a security tool: it
catches a common failure mode, forgetting to escape, and you remain
responsible for preventing XSS. See [Limitations](#limitations) for what it
deliberately does not do.

## Enabling

Add to your project's `phpstan.neon`:

```neon
includes:
    - vendor/substancephp/http/extension.neon
```

(If you use [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer),
the extension is registered automatically.)

For the rules to apply, each template must declare its variables in a `@var` block at the top of the file:
`$this` as `HtmlRenderer`, then one `@var Type $name` per template variable.

```php
<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $name
 */
?>
<h1>Hello, <?= $this->h($name) ?></h1>
```

That block is the template's contract: its variables are the action's returned array keys merged with the
shared variables (see [Shared view data](templating.md#shared-view-data)); where both provide a name, the
action's data wins. The declared types are read by the rules, not just the names: `@var int $count` makes
`<?= $count ?>` provably safe, while `@var string $name` does not. A file that does not declare `$this` is
not treated as a template, so nothing in it is checked.

## Escaping rules

Output statements — `echo`/`<?= ?>`, `print`, `die()`/`exit()`,
`printf()`/`vprintf()`, `var_dump()`, `print_r()` — are flagged as
`substancephp.unescapedOutput` unless the value is clearly safe: a literal
or provable number/boolean, the result of a renderer escape method (the short
forms `h()`, `a()`, `j()`, `c()`, `u()`, or the long forms `escapeHtml()`,
`escapeHtmlAttr()`, `escapeJs()`, `escapeCss()`, `escapeUrl()`, or the
deprecated `e()`), of `htmlspecialchars()`/`htmlentities()`, of `raw()`
(see below), of `partial()`/`content()`/`fetch()` (each emits the rendered
output of another template, which is itself checked), or of `asset()` (an asset
URL is built from your configuration, not from template data), or a
concatenation, ternary, null-coalescing or interpolated string built from safe
parts,
optionally including `raw()`. Anything the rules cannot clearly classify —
for example a dynamic call — is best effort: it may or may not be flagged.

Partials, layouts and slots are all filled by templates, so the rules check
those templates exactly like any other: each must type `$this` as
`HtmlRenderer` and escape its own output. Echoing a partial's result with
`<?= $this->partial('name') ?>`, a layout's inner output with
`<?= $this->content() ?>`, or a slot's captured output with
`<?= $this->fetch('name') ?>` is treated as safe, since the underlying
templates are responsible for escaping their own output.

## Outputting content deliberately unescaped

Pass it through `raw()` — the call alone is sufficient; no ignore comment is
needed:

```php
<?= $this->raw($trustedHtml) ?>
```

Only pass content you are certain contains no user input. (As an alternative,
you can configure `ignoreErrors` for `substancephp.unescapedOutput` in your
`phpstan.neon`.)

## Template variables

Templates are also checked against the action they render for. A template pairs with an action by the
directory convention: their paths are the same depth and differ in exactly one segment, which is the root
directory (`actions/stores.get.php` with `templates/stores.html.php`). A template that pairs with nothing is
not checked, so partials, layouts, elements and error templates are left alone.

Every variable the action's returns do not provide has to come from the application's share, so a template
may rely on `$appName` being shared (see [Shared view data](templating.md#shared-view-data)). Where the
action renders only that one template, every return has to provide the variables it declares; where the
action selects others with `setTemplate()`, each template has to be reachable from some return.

The declared types are checked as well as the names, so a template declaring `int $count` is not satisfied by
a return providing `'twelve'`. The check only speaks when it is sure: a value it can prove nothing about, such
as one typed `mixed`, is left alone.

The renderer helpers that include another template are checked in the same way. `partial()`, `layout()` and
`beginElement()` each resolve their name inside their own directory, and what a call passes has to satisfy
what the template it names declares. `fetch()` reads a slot rather than including a template, so it is not
part of this, and a call from code that is not a template, such as a test, is left alone.

These are reported rather than skipped, because skipping would leave the check quietly doing nothing:

* a return whose data has no statically known keys, such as a bare `array` or `mixed`: return an array shape;
* `setTemplate()` with anything but a literal or a constant;
* a template variable that nothing provides.

Both the action tree and the template tree have to be in the paths PHPStan analyses.

Two attributes declare the templates an action can render, for cases the convention does not cover. Both go
on the callback the action file returns, and both name templates relative to the template root, without the
suffix:

```php
#[DefaultTemplate('pages/dashboard')]
#[AltTemplates(['pages/dashboard-empty', 'pages/dashboard-error'])]
static function (): array { /* ... */ }
```

`DefaultTemplate` replaces the route's own template as the default, and `AltTemplates` adds templates the
action may select with `setTemplate()`. Neither has to be repeated for an action that follows the convention.
A declared template that no analysed file provides is reported, and so is a declaration whose names are not
literals.

## Limitations

The rules are intentionally shallow, so a clean run does not prove your
templates are XSS-safe:

* They perform no data-flow ("taint") analysis: they do not track where
  values come from, so they cannot tell trusted data from user input.
* They only check that *some* escaping happened, not that the right escaper
  was used for the context — inside an attribute `escapeHtmlAttr()` is
  required, inside `<script>` `escapeJs()`, and so on.
* They trust `raw()`, and trust that subclasses do not override the escape
  methods with non-escaping implementations.
* They only apply to templates whose `$this` is typed as `HtmlRenderer` (or
  a subclass); other files are never checked.

Treat the extension as a reminder, not a guarantee: escaping correctly is
always the template author's responsibility.
