<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>

<?php $this->beginElement('panel'); ?>
    <?php $this->start('footer'); ?><p>footer A</p><?php $this->stop(); ?>
<?php $this->endElement(); ?>
<?php $this->beginElement('panel'); ?>
    <?php $this->start('footer'); ?><p>footer B</p><?php $this->stop(); ?>
<?php $this->endElement(); ?>
<?php $this->start('footer'); ?><p>page footer</p><?php $this->stop(); ?>
[page:<?= $this->fetch('footer') ?>]
