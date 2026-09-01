<?php

return [
    'auth.authenticator' => [
        'class' => \App\Auth\Authentication\Authenticator::class,
        'arguments' => ['@session']
    ],
    'auth.use-case.signin' => [
        'class' => \App\Auth\UseCase\SignInUseCase::class,
        'arguments' => ['@entity-manager', '@user.password-encoder', '@auth.authenticator'],
        'tags' => [
            [
                'name' => 'use-case',
                'value' => 'auth.sign-in'
            ]
        ]
    ],
    'auth.use-case.signup' => [
        'class' => \App\Auth\UseCase\SignUpUseCase::class,
        'arguments' => ['@entity-manager', '@user.password-encoder', '@auth.authenticator'],
        'tags' => [
            [
                'name' => 'use-case',
                'value' => 'auth.sign-up'
            ]
        ]
    ]
];