<?php

return array_merge([
    'index' => [
        'path' => '/',
        'controller' => [\App\User\Controller\AccountController::class, 'index'],
        'methods' => ['GET']
    ]
],
require __DIR__ . '/routes/auth.php',
require __DIR__ . '/routes/oauth2.php'
);