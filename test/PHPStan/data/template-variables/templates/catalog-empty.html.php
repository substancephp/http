<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var string $message */
?>
<p><?= $this->h($message) ?></p>
