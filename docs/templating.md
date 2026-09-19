# Templating

A template is a plain PHP file ending in `.html.php`, rendered by `HtmlRenderer`.
Inside a template, `$this` is the renderer: it provides the escaping helpers and
the view facilities (partials, layouts, slots, elements). Data returned by your
action arrives as plain variables.

```
templates/
  layouts/       <- layouts, e.g. layout.html.php, site.html.php
  partials/      <- partials, e.g. store-card.html.php
  elements/      <- custom elements, e.g. card.html.php
  *.html.php     <- views/pages, e.g. stores.html.php
```

- [Rendering a template](#rendering-a-template)
- [Escaping](#escaping)
- [Partials](#partials)
- [Layouts](#layouts)
- [Slots](#slots)
- [Elements](#elements)
- [Shared view data](#shared-view-data)
- [Assets](#assets)
- [Static analysis](#static-analysis)

## Rendering a template

Your action's return value becomes the template's data, and the template file
for a route is `{templateRoot}/{path}.html.php`. An action can render a different
template with `$respond->setTemplate('other/path')` (see [responses.md](responses.md)).

```php
// actions/stores.get.php
return ['stores' => [['name' => 'Corner Shop'], ['name' => 'Central']]];
```

```php
// templates/stores.html.php, rendered for the route /stores
<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var array<int, array{name: string}> $stores */
?>
<h1>Stores</h1>

<ul>
    <?php foreach ($stores as $store): ?>
        <li><?= $this->h($store['name']) ?></li>
    <?php endforeach; ?>
</ul>
```

A template declares its variables, and their types, in a `@var` block at the top of the file. Its variables
are the action's returned array keys merged with the [shared variables](#shared-view-data), action data
winning on a name clash, and the declared types are what the static-analysis rules read (see
[static-analysis.md](static-analysis.md)).

[Back to top](#templating)

## Escaping

Escape everything that is not your own markup. Each helper targets one context;
the long forms (`escapeHtml`, `escapeHtmlAttr`, `escapeJs`, `escapeCss`,
`escapeUrl`) are aliases.

```php
<a href="<?= $this->a($store['url']) ?>"><?= $this->h($store['name']) ?></a>
<script>var data = <?= $this->j($json) ?>;</script>
<style>.card { color: <?= $this->c($color) ?>; }</style>
<img src="<?= $this->u($path) ?>">
<?= $this->raw($trustedHtml) ?>
```

[Back to top](#templating)

## Partials

Partials are small templates you reuse. They receive only the data you pass
them; `$this` is still the renderer, so the escaping helpers work inside them.

```php
<?= $this->partial('store-card', ['store' => $store]) ?>
```

```php
// templates/partials/store-card.html.php
<article>
    <h2><?= $this->h($store['name']) ?></h2>
    <?= $this->partial('store-address', $store) ?>
</article>
```

```php
// templates/partials/store-address.html.php
<p><?= $this->h($address) ?></p>
```

[Back to top](#templating)

## Layouts

A view declares its layout at the top; the layout renders after the view and
reads its output via `$this->content()`.

```php
// templates/stores.html.php
<?php $this->layout('site', ['title' => 'Stores']); ?>
```

```php
// templates/layouts/site.html.php
<html>
<head><title><?= $this->h($title) ?></title></head>
<body>
    <?= $this->content() ?>
</body>
</html>
```

If a view declares no layout, the default layout `layouts/layout.html.php` is
used (configurable via the `defaultLayout` of the application's `Templating`
configuration). Layouts can stack: a layout may itself declare a layout, and each
layer reads the one beneath it.

[Back to top](#templating)

## Slots

Fill slots in the view, read them anywhere, typically in the layout.

```php
// in the view
<?php $this->start('sidebar'); ?>
    <ul><li>All stores</li></ul>
<?php $this->stop(); ?>
```

```php
// in the layout
<aside><?= $this->fetch('sidebar', 'no sidebar') ?></aside>
```

`append()` and `prepend()` accumulate instead of replacing:

```php
<?php $this->append('scripts'); ?><script src="/a.js"></script><?php $this->stop(); ?>
<?php $this->append('scripts'); ?><script src="/b.js"></script><?php $this->stop(); ?>
```

`fetch('scripts')` yields both scripts in order. The default argument is used
only when the slot was never filled; an explicitly empty slot stays empty.

[Back to top](#templating)

## Elements

Elements wrap a body and named sub-slots in a reusable template.

```php
<?php $this->beginElement('card', ['title' => 'Corner Shop']); ?>
    <p>Open until 10pm.</p>
    <?php $this->start('footer'); ?><a href="/stores/1">Details</a><?php $this->stop(); ?>
<?php $this->endElement(); ?>
```

```php
// templates/elements/card.html.php
// $title is a param, content() is the body, fetch('footer') is a sub-slot.
<div class="card">
    <h2><?= $this->h($title) ?></h2>
    <?= $this->content() ?>
    <footer><?= $this->fetch('footer') ?></footer>
</div>
```

Sub-slots belong to their element instance: two cards with the same sub-slot
names do not collide, and their slots never leak into the page.

[Back to top](#templating)

## Shared view data

Data that many templates need (e.g. CSRF fields, navigation state, the current user, flash messages) need not
be returned by every action. Implement `ShareInterface` on a class whose public properties are the variables,
and name that class in the application's `Templating`:

```php
final readonly class AppShared implements ShareInterface
{
    public string $who;

    public function __construct(
        ServerRequestInterface $request,
        public string $appName = 'My App',
    ) {
        $this->who = $request->getHeaderLine('X-Who');
    }
}
```

```php
Application::make(
    // ...
    templating: new Templating(root: __DIR__ . '/templates', share: AppShared::class),
);
```

```php
<title><?= $this->h($appName) ?></title>
<p>Hello, <?= $this->h($who) ?></p>
```

The class is resolved once per request from the request-scoped container, so its constructor may require
request-scoped dependencies. Bind it as a container service when it needs configuration; an unbound class is
autowired, resolving each constructor parameter by type or falling back to its default. Its properties reach
every template layer (view, layout, partial and element), and are resolved only when an HTML template is
rendered. The action's own data wins on a name clash.

The set of names is fixed: a variable that only some requests have is a nullable property, so a template can
rely on every shared variable being defined. With no shared data at all, the default `EmptyShare` contributes
nothing. See [static-analysis.md](static-analysis.md) for how a template declares them.

[Back to top](#templating)

## Assets

`$this->asset()` returns a cache-busted URL for a static asset, so a file can be served with a long `max-age`
and still be refetched when it changes:

```php
<link rel="stylesheet" href="<?= $this->asset('css/app.css') ?>">
<!-- /assets/css/app.css?v=9f2c1a7e -->
```

The path is relative to the assets' root, configured on the application's `Templating`:

```php
new Templating(
    root: __DIR__ . '/templates',
    assets: new Assets(root: __DIR__ . '/public', baseUrl: '/assets'),
);
```

The token is the asset's content hash, computed lazily and memoised, so it changes exactly when the file
does. To avoid hashing entirely, pass a `tokenResolver`, consulted per path. Return a token to use it (e.g. a
deploy-wide version, or a build manifest's hashed filename), or null to fall back to the content hash:

```php
new Assets(
    root: __DIR__ . '/public',
    baseUrl: '/assets',
    tokenResolver: fn (string $path): ?string => $_ENV['ASSET_VERSION'] ?? null,
);
```

A missing asset throws `MissingAssetException`. Because `asset()` builds its URL from your configuration
rather than from template data, the static-analysis rules treat its output as safe.

[Back to top](#templating)

## Static analysis

The bundled PHPStan extension flags unescaped output in templates. For it to
apply, type `$this` as `HtmlRenderer` (as in the first example) and annotate
your data variables. See [static-analysis.md](static-analysis.md), especially re. the
limitations of this extension.

[Back to top](#templating)
