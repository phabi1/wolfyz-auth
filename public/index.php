<?php

require __DIR__ . '/../vendor/autoload.php';

$env = getenv('APP_ENV');
if (!$env) {
    $env = 'production';
}

define('APP_ENV', $env);
define('APP_DIR', __DIR__ . '/..');
define('CONFIG_DIR', APP_DIR . '/config');
define('CACHE_DIR', APP_DIR . '/cache');

(new App\Core\Kernel\HttpKernel)->run();
