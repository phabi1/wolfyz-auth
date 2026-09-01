<?php

return [
    'oauth2.server' => [
        'factory' => [\App\OAuth2\Factory\OAuth2ServerFactory::class, 'create'],
        'arguments' => [
            '@oauth2.client_repository',
            '@oauth2.scope_repository',
            '@oauth2.access_token_repository',
            '@oauth2.refresh_token_repository',
            '@oauth2.auth_code_repository',
            '!oauth2.private_key',
            '!oauth2.passphrase',
            '!oauth2.encryption_key',
        ]
    ],
    'oauth2.scope_repository' => [
        'factory' => [\App\OAuth2\Repository\ScopeRepositoryFactory::class, 'create'],
           'arguments' => ['@entity-manager']
    ],
    'oauth2.access_token_repository' => [
        'factory' => [\App\OAuth2\Repository\AccessTokenRepositoryFactory::class, 'create'],
        'arguments' => ['@entity-manager']
    ],
    'oauth2.client_repository' => [
        'factory' => [\App\OAuth2\Repository\ClientRepositoryFactory::class, 'create'],
        'arguments' => ['@entity-manager']
    ],
    'oauth2.refresh_token_repository' => [
        'factory' => [\App\OAuth2\Repository\RefreshTokenRepositoryFactory::class, 'create'],
        'arguments' => ['@entity-manager']
    ],
    'oauth2.auth_code_repository' => [
        'factory' => [\App\OAuth2\Repository\AuthCodeRepositoryFactory::class, 'create'],
        'arguments' => ['@entity-manager']
    ],
];