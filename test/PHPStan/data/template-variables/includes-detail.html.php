<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>
<div>
    <?= $this->partial('detail', ['count' => 'many']) ?>
</div>
