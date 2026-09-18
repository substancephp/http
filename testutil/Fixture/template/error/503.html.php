<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var int $statusCode
 * @var string $appName
 */
?>
<h1><?= $this->h((string) $statusCode) ?></h1>
<p><?= $this->h($appName) ?></p>
