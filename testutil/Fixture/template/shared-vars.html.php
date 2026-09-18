<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $appName
 * @var string $who
 */
?>
<p><?= $this->h($appName) ?> / <?= $this->h($who) ?></p>
