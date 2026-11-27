<?php

return [
    'user-profile' => [
        'type' => 'literal',
        'path' => '/profile',
        'controller' => 'user.profile',
        'action' => 'index',
    ],
    'user-api-collection' => [
        'type' => 'literal',
        'path' => '/api/users',
        'controller' => 'user.api',
        'action' => 'collection',
    ],
    'user-api-item' => [
        'type' => 'segment',
        'path' => '/api/users/{id}',
        'constraints' => ['id' => '[A-Za-z0-9_-]{1,64}'],
        'controller' => 'user.api',
        'action' => 'item',
    ],
    'user-api-permissions' => [
        'type' => 'literal',
        'path' => '/api/permissions',
        'controller' => 'user.role-api',
        'action' => 'permissions',
    ],
    'user-api-roles' => [
        'type' => 'literal',
        'path' => '/api/roles',
        'controller' => 'user.role-api',
        'action' => 'collection',
    ],
    'user-api-role' => [
        'type' => 'segment',
        'path' => '/api/roles/{id}',
        'constraints' => ['id' => '[a-z0-9][a-z0-9_-]{0,79}'],
        'controller' => 'user.role-api',
        'action' => 'item',
    ],
];