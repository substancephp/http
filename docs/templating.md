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
- [Static analysis](#static-analysis)

## Rendering a template

Your action's return value becomes the template's data, and the template file
for a route is `{templateRoot}/{path}.html.php`.

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
used (configurable via `substance.default-layout`). Layouts can stack: a layout
may itself declare a layout, and each layer reads the one beneath it.

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

## Static analysis

The bundled PHPStan extension flags unescaped output in templates. For it to
apply, type `$this` as `HtmlRenderer` (as in the first example) and annotate
your data variables. See [static-analysis.md](static-analysis.md), especially re. the
limitations of this extension.

[Back to top](#templating)
