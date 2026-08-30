<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/** @var HtmlRenderer $this */
?>

<?php $this->beginElement('card', ['title' => 'Hi']); ?>
    <p>card body</p>
<?php $this->endElement(); ?>
