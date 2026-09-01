<?php

return [
    'oauth2-authorize' => [
        'path' => 'oauth2/authorize',
        'controller' => [\App\OAuth2\Controller\AuthController::class, 'authorize'],
        'methods' => ['GET'],
    ],
    'oauth2-token' => [
        'path' => 'oauth2/token',
        'controller' => [\App\OAuth2\Controller\AuthController::class, 'token'],
        'methods' => ['POST'],
    ],
    'oauth2-introspect' => [
        'path' => '.well-known/openid-configuration',
        'controller' => [\App\OAuth2\Controller\AuthController::class, 'introspect'],
        'methods' => ['GET', 'OPTIONS'],
    ]
];