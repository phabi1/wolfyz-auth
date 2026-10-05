<?php

use App\Core\Entity\Definition\Field;
return [
    'oidc-client' => [
        'table' => 'auth_oauth_client',
        'repository' => 'oidc-client',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER, 'readonly' => true],
            'client_id' => ['type' => Field::TYPE_STRING, 'unique' => true],
            'client_secret' => ['type' => Field::TYPE_STRING],
            'redirect_uris' => ['type' => Field::TYPE_ARRAY],
            'grant_types' => ['type' => Field::TYPE_ARRAY],
            'scopes' => ['type' => Field::TYPE_STRING],
        ],
    ],
    'oidc-scope' => [
        'table' => 'auth_oauth_scope',
        'repository' => 'oidc-scope',
        'primary_keys' => ['name'],
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER, 'readonly' => true],
            'name' => ['type' => Field::TYPE_STRING, 'unique' => true],
            'description' => ['type' => Field::TYPE_STRING],
        ],
    ],
    'oidc-access-token' => [
        'table' => 'auth_oauth_access_token',
        'primary_keys' => ['id'],
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => Field::TYPE_STRING, 'readonly' => true],
            'client_id' => ['type' => Field::TYPE_STRING],
            'user_id' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'scopes' => ['type' => Field::TYPE_STRING],
            'expires_at' => ['type' => Field::TYPE_DATETIME],
            'revoked' => ['type' => Field::TYPE_BOOLEAN, 'default' => false],
        ],
    ],
    'oidc-refresh-token' => [
        'table' => 'auth_oauth_refresh_token',
        'primary_keys' => ['id'],
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => Field::TYPE_STRING, 'readonly' => true],
            'access_token_id' => ['type' => Field::TYPE_STRING],
            'expires_at' => ['type' => Field::TYPE_DATETIME],
            'revoked' => ['type' => Field::TYPE_BOOLEAN, 'default' => false],
        ],
    ],
    'oidc-auth-code' => [
        'table' => 'auth_oauth_auth_code',
        'primary_keys' => ['id'],
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => Field::TYPE_STRING, 'readonly' => true],
            'client_id' => ['type' => Field::TYPE_STRING],
            'user_id' => ['type' => Field::TYPE_STRING, 'nullable' => true],
            'scopes' => ['type' => Field::TYPE_STRING],
            'nonce' => ['type' => Field::TYPE_STRING],
            'expires_at' => ['type' => Field::TYPE_DATETIME],
            'revoked' => ['type' => Field::TYPE_BOOLEAN, 'default' => false],
        ],
    ],
];