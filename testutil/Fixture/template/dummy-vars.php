<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $word
 * @var int $count
 * @var string $tag
 */
?>

<html lang="en">
<head><title>Hi</title></head>
<body>
    <p>Hello, <?= $this->e($word) ?></p>
    <p>Count is <?= $count ?></p>
    <p>Unescaped: <?= $tag ?></p>
    <p>Escaped: <?= $this->e($tag) ?></p>
</body>
</html>