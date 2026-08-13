<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $wordA
 * @var int $count
 * @var string $wordB
 * @var string $wordC
 */
?>

<html lang="en">
    <head>
    <meta charset="utf8">
    <title>Hi</title>
    </head>
    <body>
        <p>Hello, <?= $this->e($wordA) ?></p>
        <p>Count is <?= $count ?></p>
        <p>Unescaped A: <?= $wordB ?></p>
        <p>Escaped A: <?= $this->e($wordB) ?></p>
        <p>Escaped B: <?= $this->e($wordC) ?></p>
    </body>
</html>
