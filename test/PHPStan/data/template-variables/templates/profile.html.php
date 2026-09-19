<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var int $id */
/** @var string $name */
?>
<h1><?= $this->h($name) ?></h1>
<p><?= $this->h((string) $id) ?></p>
