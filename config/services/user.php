<?php

return [
    'user.password-encoder' => [
        'class' => App\User\Password\PasswordEncoder::class,
    ],
    'user.token' => [
        'class' => App\User\Token\TokenService::class,
        'arguments' => ['@entity.manager'],
    ],
    'user.repository.user' => [
        'class' => App\User\Repository\UserRepository::class,
        'arguments' => [
            '@user.password-encoder',
        ],
        'tags' => [
            ['name' => 'entity.repository', 'value' => 'user']
        ]
    ],
    'user.controller.profile' => [
        'class' => App\User\Controller\ProfileController::class,
        'tags' => [
            ['name' => 'controller', 'value' => 'user.profile']
        ]
    ],
    'user.roles' => [
        'class' => App\User\Role\RoleService::class,
        'arguments' => ['@entity.manager'],
    ],
    'user.admin' => [
        'class' => App\User\Admin\UserAdminService::class,
        'arguments' => ['@entity.manager', '@user.roles'],
    ],
    'user.api.guard' => [
        'class' => App\User\Api\UserApiGuard::class,
        'arguments' => ['@oidc.access-token-validator', '@user.roles'],
    ],
    'user.controller.api' => [
        'class' => App\User\Controller\UserApiController::class,
        'tags' => [
            ['name' => 'controller', 'value' => 'user.api']
        ]
    ],
    'user.controller.role-api' => [
        'class' => App\User\Controller\RoleApiController::class,
        'tags' => [
            ['name' => 'controller', 'value' => 'user.role-api']
        ]
    ],
];
