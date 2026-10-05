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
    ]
];