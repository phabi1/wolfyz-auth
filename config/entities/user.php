<?php

use App\Core\Entity\Definition\Field;
return [
    'user' => [
        'repository' => 'user',
        'table' => 'auth_user',
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => Field::TYPE_STRING],
            'name' => ['type' => Field::TYPE_STRING],
            'password_hash' => ['type' => Field::TYPE_STRING],
            'email' => ['type' => Field::TYPE_STRING],
            'email_verified' => ['type' => Field::TYPE_BOOLEAN],
            'firstname' => ['type' => Field::TYPE_STRING],
            'lastname' => ['type' => Field::TYPE_STRING],
        ],
    ],
    'user-token' => [
        'repository' => 'user-token',
        'table' => 'auth_user_token',
        'auto_increment' => false,
        'fields' => [
            'id' => ['type' => Field::TYPE_STRING],
            'user_id' => ['type' => Field::TYPE_STRING],
            'token_type' => ['type' => Field::TYPE_STRING],
            'token' => ['type' => Field::TYPE_STRING],
            'expires_at' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
        ],
    ],
];