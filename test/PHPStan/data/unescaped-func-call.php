<?php

use SubstancePHP\HTTP\Renderer\HtmlRenderer;

/**
 * @var HtmlRenderer $this
 * @var string $name
 * @var string $format
 * @var list<string> $args
 */
printf('<p>%s</p>', $this->h($name));
printf('<p>%s</p>', $name);
printf('<p>%s</p>', $this->raw($name));
printf($format, $name);
vprintf('<p>%s</p>', [$this->h($name)]);
vprintf('<p>%s</p>', $args);
printf('constant');
var_dump($name);
print_r($name);
print_r($name, true);
$fn = 'printf';
$fn('<p>%s</p>', $name);
call_user_func('printf', '<p>%s</p>', $name);
call_user_func('var_dump', $name);
$fn2 = 'print_r';
$fn2($name);
$fn3 = ['printf'];
$fn3('<p>%s</p>', $name);
call_user_func_array(['printf'], ['<p>%s</p>', $name]);
