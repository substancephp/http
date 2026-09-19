<?php

declare(strict_types=1);

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
/** @var bool $emit */
?>
<p><?= $this->h((string) $emit) ?></p>
<?php if (! empty($emit)): ?>
    <span><?= $this->h('on') ?></span>
<?php endif; ?>
