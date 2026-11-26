<?php

/**
 * Phinx configuration.
 *
 * The database connection is read from the application's own parameters file
 * (config/parameters.<APP_ENV>.php), so migrations always target the same
 * database as the application.
 */

$env = getenv('APP_ENV') ?: 'production';

// Constants defined by public/index.php and bin/console, which the
// parameters files may use (e.g. APP_DIR . '/certs/...')
foreach ([
    'APP_ENV' => $env,
    'APP_DIR' => __DIR__,
    'CONFIG_DIR' => __DIR__ . '/config',
    'CACHE_DIR' => __DIR__ . '/cache',
] as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}

$parametersFile = __DIR__ . '/config/parameters.' . $env . '.php';
if (!is_file($parametersFile)) {
    throw new RuntimeException(sprintf('Parameters file "%s" not found (APP_ENV=%s).', $parametersFile, $env));
}

$db = (require $parametersFile)['db'] ?? null;
if (!is_array($db) || empty($db['dsn'])) {
    throw new RuntimeException(sprintf('Missing "db.dsn" in "%s".', $parametersFile));
}

if (!preg_match('/(?:^|[:;])dbname=([^;]+)/', $db['dsn'], $matches)) {
    throw new RuntimeException('Unable to read the database name from "db.dsn".');
}

$pdo = new PDO($db['dsn'], $db['username'] ?? null, $db['password'] ?? null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds' => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_environment' => 'app',
        'app' => [
            'name' => $matches[1],
            'connection' => $pdo,
            // The API shares the same database: keep a dedicated log table.
            'migration_table' => 'auth_migrations',
        ],
    ],
    'version_order' => 'creation',
];
