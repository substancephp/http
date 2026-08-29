<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $name
 * @var int $count
 * @var string|null $maybeNull
 */
?>
<p><?= $this->h($name) ?></p>
<p><?= $this->escapeHtmlAttr($name) ?></p>
<p><?= $this->h($name) . '!' ?></p>
<p><?= 'a' . $this->escapeHtml($name) ?></p>
<p><?= $this->h($name) ?: 'default' ?></p>
<p><?= htmlspecialchars($name) ?></p>
<p><?= $this->a($name) ?></p>
<p><?= $this->j($name) ?></p>
<p><?= $this->c($name) ?></p>
<p><?= $this->u($name) ?></p>
<p><?= $name ?></p>
<p><?= $this->raw($name) ?></p>
<p><?= $name . '!' ?></p>
<p><?= $this->h($name) . $this->raw($name) ?></p>
<p><?= $count ?></p>
<p><?= $maybeNull ?></p>
<p><?= "Hello {$this->h($name)}" ?></p>
<p><?= $this->EscapeHtml($name) ?></p>
<p><?= $this->RAw($name) ?></p>
<p><?= sprintf('%s', $name) ?></p>
<p><?= $name . $count ?></p>
<p><?= $this->partial('share', ['name' => $name]) ?></p>
