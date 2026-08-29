<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $word
 */
?>
[<?= $this->partial('inner', ['word' => $word]) ?>]
