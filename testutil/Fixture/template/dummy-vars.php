<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $word
 * @var string $unsafeWord
 */
?>
<!DOCTYPE html>
<html lang="en">
<body>
    <p>Hello, <?= $this->h($word) ?></p>
    <p>Unescaped: <?= $this->raw($unsafeWord) ?></p>
    <p>Escaped: <?= $this->escapeHtml($unsafeWord) ?></p>
</body>
</html>
