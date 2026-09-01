<?php

return [
    'db' => [
        'dsn' => 'mysql:host=' . getenv('DB_HOST') . ':' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        ],
    ],
    'oauth2' => [
        'issuer' => getenv('OAUTH2_ISSUER'),
        'private_key' => getenv('OAUTH2_PRIVATE_KEY'),
        'passphrase' => getenv('OAUTH2_PASSPHRASE'),
        'encryption_key' => getenv('OAUTH2_ENCRYPTION_KEY')
    ]
];