<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $path
 * @var string $data
 * @var string $started
 * @var string $result
 */
?>
<?= $this->h($path) ?>|<?= $this->h($data) ?>|<?= $this->h($started) ?>|<?= $this->h($result) ?>
