<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>
[<?= $this->fetch('sidebar', 'fallback') ?>]
<scripts><?= $this->fetch('scripts') ?></scripts>
<empty><?= $this->fetch('empty', 'FALLBACK') ?></empty>
<replace><?= $this->fetch('replace', 'REPLACE') ?></replace>
<?= $this->content() ?>
