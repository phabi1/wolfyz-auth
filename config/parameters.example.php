<?php

return [
    'app_env' => getenv('APP_ENV') ?: 'production',
    'db' => [
        'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
    ],
    'oauth2' => [
        'issuer' => getenv('OAUTH2_ISSUER'),
        'private_key' => getenv('OAUTH2_PRIVATE_KEY'),
        'public_key' => dirname(getenv('OAUTH2_PRIVATE_KEY')) . '/oauth2-public.key',
        'passphrase' => getenv('OAUTH2_PASSPHRASE') ?: null,
        'encryption_key' => getenv('OAUTH2_ENCRYPTION_KEY'),
        'access_token_ttl' => 'PT1H',
        'refresh_token_ttl' => 'P1M',
        'auth_code_ttl' => 'PT10M',
    ],
    'mail' => [
        'from' => [
            'email' => getenv('MAIL_FROM_EMAIL') ?: '',
            'name' => getenv('MAIL_FROM_NAME') ?: 'Wolf Auth',
        ],
        'smtp' => [
            'host' => getenv('SMTP_HOST') ?: '',
            'port' => (int) (getenv('SMTP_PORT') ?: 587),
            'username' => getenv('SMTP_USERNAME') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
            'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
        ],
    ],
];
