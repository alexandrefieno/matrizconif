<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$view = dirname(__DIR__) . '/frontend/templates/home.php';
if (!is_file($view)) {
    http_response_code(500);
    exit('Template público indisponível.');
}

require $view;
