<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>
<div class="panel"><?= $this->content() ?>[<?= $this->fetch('footer', 'none') ?>]</div>
