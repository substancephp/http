<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var array<string, string> $row */
?>
<div>
    <?= $this->partial('detail', $row) ?>
</div>
