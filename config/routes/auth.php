<?php

return [
    'signin' => [
        'path' => '/signin',
        'controller' => [\App\Auth\Controller\SignController::class, 'signin'],
        'methods' => ['GET', 'POST']
    ],
    'signup' => [
        'path' => '/signup',
        'controller' => [\App\Auth\Controller\SignController::class, 'signup'],
        'methods' => ['GET', 'POST']
    ],
    'signout' => [
        'path' => '/signout',
        'controller' => [\App\Auth\Controller\SignController::class, 'signout'],
        'methods' => ['GET', 'POST']
    ],
];