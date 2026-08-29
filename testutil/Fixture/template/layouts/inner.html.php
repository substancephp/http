<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>

<?php $this->layout('outer'); ?>
[inner:<?= $this->content() ?>]
