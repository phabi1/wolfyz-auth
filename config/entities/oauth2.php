<?php

use App\Core\Entity\Definition\Field;

return [
    'oauth2.client' => [
        'table' => 'auth_oauth2_client',
        'fields' => [
            'id' => [
                'type' => Field::TYPE_INTEGER
            ],
            'title' => [
                'type' => Field::TYPE_STRING
            ],
            'client_id' => [
                'type' => Field::TYPE_STRING
            ],
            'client_secret' => [
                'type' => Field::TYPE_STRING
            ],
            'redirect_uri' => [
                'type' => Field::TYPE_STRING
            ],
            'grant_types' => [
                'type' => Field::TYPE_ARRAY
            ],
            'scope' => [
                'type' => Field::TYPE_STRING
            ],
            'user_id' => [
                'type' => Field::TYPE_INTEGER,
                'nullable' => true
            ]
        ]
    ],
    'oauth2.scope' => [
        'table' => 'auth_oauth2_scope',
        'fields' => [
            'id' => [
                'type' => Field::TYPE_INTEGER
            ],
            'name' => [
                'type' => Field::TYPE_STRING
            ],
            'description' => [
                'type' => Field::TYPE_STRING,
                'nullable' => true
            ]
        ]
    ],
    'oauth2.access_token' => [
        'table' => 'auth_oauth2_access_token',
        'fields' => [
            'id' => [
                'type' => Field::TYPE_INTEGER
            ],
            'access_token' => [
                'type' => Field::TYPE_STRING
            ],
            'client_id' => [
                'type' => Field::TYPE_STRING
            ],
            'user_id' => [
                'type' => Field::TYPE_INTEGER,
                'nullable' => true
            ],
            'expires' => [
                'type' => Field::TYPE_DATETIME
            ],
            'scope' => [
                'type' => Field::TYPE_STRING,
                'nullable' => true
            ]
        ]
    ],
    'oauth2.refresh_token' => [
        'table' => 'auth_oauth2_refresh_token',
        'fields' => [
            'id' => [
                'type' => Field::TYPE_INTEGER
            ],
            'access_token' => [
                'type' => Field::TYPE_STRING
            ],
            'refresh_token' => [
                'type' => Field::TYPE_STRING
            ],
            'client_id' => [
                'type' => Field::TYPE_STRING
            ],
            'expires' => [
                'type' => Field::TYPE_DATETIME
            ],
            'scope' => [
                'type' => Field::TYPE_STRING
            ]
        ]
    ],
    'oauth2.auth_code' => [
        'table' => 'auth_oauth2_auth_code',
        'fields' => [
            'id' => [
                'type' => Field::TYPE_INTEGER
            ],
            'code' => [
                'type' => Field::TYPE_STRING
            ],
            'user_id' => [
                'type' => Field::TYPE_INTEGER,
                'nullable' => true
            ],
            'client_id' => [
                'type' => Field::TYPE_STRING
            ],
            'scope' => [
                'type' => Field::TYPE_STRING,
                'nullable' => true
            ],
            'expires' => [
                'type' => Field::TYPE_DATETIME
            ]
        ]
    ]
];