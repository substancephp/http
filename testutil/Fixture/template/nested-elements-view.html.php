<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>

<?php $this->beginElement('card', ['title' => 'Nested']); ?>
    <?php $this->beginElement('panel'); ?><p>inner</p><?php $this->endElement(); ?>
<?php $this->endElement(); ?>
