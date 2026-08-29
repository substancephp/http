<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $url
 * @var string $label
 */
?>
<a href="<?= $this->u($url) ?>"><?= $this->h($label) ?></a>
