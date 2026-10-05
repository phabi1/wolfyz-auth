<?php
return [
    'auth-password-forgot' => [
        'type' => 'literal',
        'path' => '/auth/password/forgot',
        'controller' => 'auth.password',
        'action' => 'forgot',
    ],
    'auth-password-reset' => [
        'type' => 'literal',
        'path' => '/auth/password/reset',
        'controller' => 'auth.password',
        'action' => 'reset',
    ],
    'auth-signin' =>
        [
            'type' => 'literal',
            'path' => '/signin',
            'controller' => 'auth.sign',
            'action' => 'signin',
        ],
    'auth-signup' =>
        [
            'type' => 'literal',
            'path' => '/signup',
            'controller' => 'auth.sign',
            'action' => 'signup',
        ],
    'auth-signout' =>
        [
            'type' => 'literal',
            'path' => '/signout',
            'controller' => 'auth.sign',
            'action' => 'signout',
        ]
];