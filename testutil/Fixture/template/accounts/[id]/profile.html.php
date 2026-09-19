<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var array{id: string} $data
 */
?>
<h1>Profile <?= $this->h($data['id']) ?></h1>
