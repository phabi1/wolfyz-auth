<?php

return [
    'auth-provider' => [
        'table' => 'auth_provider',
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => 'string', 'required' => true],
            'title' => ['type' => 'string', 'required' => true],
            'provider_type' => ['type' => 'string', 'required' => true],
            'active' => ['type' => 'boolean', 'required' => true],
        ],
    ],
    'auth-user-provider' => [
        'table' => 'auth_user_provider',
        'primary_key' => ['user_id', 'auth_provider_id'],
        'auto_increment' => false,
        'fields' => [
            'user_id' => ['type' => 'string', 'required' => true],
            'auth_provider_id' => ['type' => 'string', 'required' => true],
            'value' => ['type' => 'string', 'required' => true],
        ],
    ],
];