<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $title
 */
?>
<div class="card"><h2><?= $this->h($title) ?></h2><?= $this->content() ?></div>
