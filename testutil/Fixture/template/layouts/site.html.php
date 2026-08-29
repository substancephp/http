<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $title
 */
?>
<html>
<head><title><?= $this->h($title) ?></title></head>
<body><?= $this->content() ?></body>
</html>
