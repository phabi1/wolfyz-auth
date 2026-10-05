<?php
return [
    'oidc-authorize' =>
        [
            'type' => 'literal',
            'path' => '/oidc/authorize',
            'controller' => 'oidc.auth',
            'action' => 'authorize',
        ],
     'oidc-token' =>
        [
            'type' => 'literal',
            'path' => '/oidc/token',
            'controller' => 'oidc.auth',
            'action' => 'token',
        ],
    'oidc-userinfo' =>
        [
            'type' => 'literal',
            'path' => '/oidc/userinfo',
            'controller' => 'oidc.auth',
            'action' => 'userinfo',
        ],
    'oidc-introspect' =>
        [
            'type' => 'literal',
            'path' => '/oidc/.well-known/openid-configuration',
            'controller' => 'oidc.auth',
            'action' => 'introspect',
        ],
    'oidc-jwks' =>
        [
            'type' => 'literal',
            'path' => '/oidc/.well-known/jwks.json',
            'controller' => 'oidc.auth',
            'action' => 'jwks',
        ],
    
];