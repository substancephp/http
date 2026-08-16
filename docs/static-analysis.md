# Static analysis: catching unescaped output

`substancephp/http` bundles a small PHPStan extension that nudges you to
escape the output in `HtmlRenderer` templates. It is a best-effort lint, not
a security tool: it catches a common failure mode — forgetting to escape —
and you remain responsible for preventing XSS. See
[Limitations](#limitations) for what it deliberately does not do.

## Enabling

Add to your project's `phpstan.neon`:

```neon
includes:
    - vendor/substancephp/http/extension.neon
```

(If you use [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer),
the extension is registered automatically.)

For the rules to apply, each template must type `$this` as `HtmlRenderer`,
which is required anyway for PHPStan to understand the template's API:

```php
<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $name */
?>
<h1>Hello, <?= $this->h($name) ?></h1>
```

## What the rules flag

Output statements — `echo`/`<?= ?>`, `print`, `die()`/`exit()`,
`printf()`/`vprintf()`, `var_dump()`, `print_r()` — are flagged as
`substancephp.unescapedOutput` unless the value is clearly safe: a literal
or provable number/boolean, the result of a renderer escape method (`h()`,
`e()`, `escapeHtml()`, `escapeHtmlAttr()`, `escapeJs()`, `escapeCss()`,
`escapeUrl()`), of `htmlspecialchars()`/`htmlentities()`, or of `raw()` (see
below), or a concatenation, ternary, null-coalescing or interpolated string
built from safe parts, optionally including `raw()`. Anything the rules
cannot clearly classify — for example a dynamic call — is best effort: it
may or may not be flagged.

## Outputting content deliberately unescaped

Pass it through `raw()` — the call alone is sufficient; no ignore comment is
needed:

```php
<?= $this->raw($trustedHtml) ?>
```

Only pass content you are certain contains no user input. (As an alternative,
you can configure `ignoreErrors` for `substancephp.unescapedOutput` in your
`phpstan.neon`.)

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
