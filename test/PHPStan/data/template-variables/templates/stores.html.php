<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var array<int, string> $stores */
/** @var string $title */
?>
<h1><?= $this->h($title) ?></h1>
<ul>
    <?php foreach ($stores as $store): ?>
        <li><?= $this->h($store) ?></li>
    <?php endforeach; ?>
</ul>
