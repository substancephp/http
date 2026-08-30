<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>

<?php $this->beginElement('nonexistent'); ?><p>x</p><?php $this->endElement(); ?>
