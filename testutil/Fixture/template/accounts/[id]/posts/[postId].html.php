<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var array{id: string, postId: string} $data
 */
?>
<h1>Post <?= $this->h($data['postId']) ?> of <?= $this->h($data['id']) ?></h1>
