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

// Under CGI/FastCGI, the Authorization header only reaches PHP through the
// .htaccess rewrite rule, renamed REDIRECT_HTTP_AUTHORIZATION after the
// internal redirect to index.php.
if (empty($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

(new App\Core\Kernel\HttpKernel)->run();
