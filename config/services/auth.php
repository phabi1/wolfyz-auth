<?php

return [
    'auth.controller.home' => [
        'class' => App\Auth\Controller\HomeController::class,
        'tags' => [['name' => 'controller', 'value' => 'auth.home']],
    ],
    'auth.password-reset' => [
        'class' => App\Auth\Password\PasswordResetService::class,
        'arguments' => ['@entity.manager', '@user.token', '@mailer', '!base_url'],
    ],
    'auth.controller.password' => [
        'class' => App\Auth\Controller\PasswordController::class,
        'tags' => [['name' => 'controller', 'value' => 'auth.password']],
    ],
    'auth.authentication' => [
        'class' => App\Auth\Authentication\AuthenticationService::class,
        'arguments' => [
            '@entity.manager',
            '@user.password-encoder',
            '@session'
        ]
    ],
    'auth.controller.sign' => [
        'class' => App\Auth\Controller\SignController::class,
        'tags' => [['name' => 'controller', 'value' => 'auth.sign']]
    ],
];