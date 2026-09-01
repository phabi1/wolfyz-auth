<?php

require __DIR__ . '/../vendor/autoload.php';

define('APP_DIR', realpath(__DIR__ . '/..'));
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('CONFIG_DIR', APP_DIR . '/config');
define('CACHE_DIR', APP_DIR . '/cache');

\App\Application::run();