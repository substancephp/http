<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $error
 * @var int $statusCode
 */
?>
<!DOCTYPE html>
<html lang="en">
<body>
    <h1>Unprocessable Entity</h1>
    <p><?= $this->h($error) ?></p>
    <p>Specific</p>
</body>
</html>
