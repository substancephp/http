<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>

<?php $this->layout('slot-layout'); ?>
<?= $this->partial('sidebar-filler') ?>
<?php $this->append('scripts'); ?><script>a</script><?php $this->stop(); ?>
<?php $this->append('scripts'); ?><script>b</script><?php $this->stop(); ?>
<?php $this->prepend('scripts'); ?><script>0</script><?php $this->stop(); ?>
<?php $this->start('empty'); ?><?php $this->stop(); ?>
<?php $this->start('replace'); ?><p>first</p><?php $this->stop(); ?>
<?php $this->start('replace'); ?><p>second</p><?php $this->stop(); ?>
<p>body</p>
