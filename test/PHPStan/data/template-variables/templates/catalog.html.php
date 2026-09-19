<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var array<int, string> $items */
?>
<ul>
    <?php foreach ($items as $item): ?>
        <li><?= $this->h($item) ?></li>
    <?php endforeach; ?>
</ul>
