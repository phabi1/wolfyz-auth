<?php

use App\Core\Entity\Definition\Field;
use App\Core\Entity\Definition\Relation;

return [
    'user' => [
        'repository' => \App\User\Repository\UserRepository::class,
        'table' => 'auth_user',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'username' => ['type' => Field::TYPE_STRING, 'required' => true, 'unique' => true],
            'email' => ['type' => Field::TYPE_STRING, 'required' => true, 'unique' => true],
            'password' => ['type' => Field::TYPE_STRING, 'required' => true],
            'firstname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'lastname' => ['type' => Field::TYPE_STRING, 'required' => true],
            'status' => ['type' => Field::TYPE_STRING],
            'created_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
            'updated_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
        ],
        'relations' => [
            'roles' => [
                'type' => Relation::TYPE_ONE_TO_MANY,
                'entity' => 'role',
                'table' => 'auth_user_role',
                'foreign_key' => 'user_id',
                'related_key' => 'role_id',
            ],
        ],
    ],
    'user-token' => [
        'table' => 'auth_user_token',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'token_type' => ['type' => Field::TYPE_STRING, 'required' => true],
            'user_id' => ['type' => Field::TYPE_INTEGER, 'required' => true],
            'token' => ['type' => Field::TYPE_STRING, 'required' => true, 'unique' => true],
            'expires_at' => ['type' => Field::TYPE_DATETIME, 'nullable' => true],
            'created_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
            'updated_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
        ],
    ],
    'role' => [
        'table' => 'auth_role',
        'fields' => [
            'id' => ['type' => Field::TYPE_INTEGER],
            'name' => ['type' => Field::TYPE_STRING, 'required' => true, 'unique' => true],
            'created_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
            'updated_at' => ['type' => Field::TYPE_DATETIME, 'required' => true],
        ]
    ]
];
