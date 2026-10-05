<?php

use App\Oidc\Server\Server;
return [
    'oidc.server.repository.client' => [
        'class' => App\Oidc\Server\Repository\ClientRepository::class,
        'arguments' => ['@entity.manager'],
    ],
    'oidc.server.repository.scope' => [
        'class' => App\Oidc\Server\Repository\ScopeRepository::class,
        'arguments' => ['@entity.manager'],
    ],
    'oidc.server.repository.user' => [
        'class' => App\Oidc\Server\Repository\UserRepository::class,
        'arguments' => ['@entity.manager'],
    ],
    'oidc.server.repository.access_token' => [
        'class' => App\Oidc\Server\Repository\AccessTokenRepository::class,
        'arguments' => ['@entity.manager'],
    ],
    'oidc.server.repository.refresh_token' => [
        'class' => App\Oidc\Server\Repository\RefreshTokenRepository::class,
        'arguments' => ['@entity.manager'],
    ],
    'oidc.server.repository.auth_code' => [
        'class' => App\Oidc\Server\Repository\AuthCodeRepository::class,
        'arguments' => ['@entity.manager'],
    ],
    'oidc.server' => [
        'class' => App\Oidc\Server\Server::class,
        'arguments' => [
            '@parameters',
            '@oidc.server.repository.client',
            '@oidc.server.repository.scope',
            '@oidc.server.repository.user',
            '@oidc.server.repository.access_token',
            '@oidc.server.repository.refresh_token',
            '@oidc.server.repository.auth_code',
            '@oidc.jwks',
        ],
    ],
    'oidc.introspection' => [
        'class' => App\Oidc\Introspection::class,
        'arguments' => ['!oidc.issuer'],
    ],
    'oidc.userinfo' => [
        'class' => App\Oidc\Userinfo::class,
        'arguments' => ['@oidc.server', '@oidc.server.repository.user'],
    ],
    'oidc.jwks' => [
        'class' => App\Oidc\Jwks::class,
    ],
    'oidc.controller.auth' => [
        'class' => App\Oidc\Controller\AuthController::class,
        'tags' => [['name' => 'controller', 'value' => 'oidc.auth']],
    ],
    'oidc.repository.client' => [
        'class' => App\Oidc\Repository\ClientEntityRepository::class,
        'tags' => [['name' => 'entity.repository', 'value' => 'oidc-client']],
    ],
    'oidc.repository.scope' => [
        'class' => App\Oidc\Repository\ScopeEntityRepository::class,
        'tags' => [['name' => 'entity.repository', 'value' => 'oidc-scope']],
    ],
    'oidc.command.create-certificate' => [
        'class' => App\Oidc\Command\CreateCertificateCommand::class,
        'tags' => [['name' => 'command', 'command' => 'oidc:mkcert']],
    ],
    'oidc.command.create-client' => [
        'class' => App\Oidc\Command\CreateClientCommand::class,
        'arguments' => ['@use-case-bus'],
        'tags' => [['name' => 'command', 'command' => 'oidc:create-client']],
    ],
    'oidc.use-case.create-client' => [
        'class' => App\Oidc\UseCase\CreateClientUseCase::class,
        'arguments' => ['@entity.manager'],
        'tags' => [['name' => 'use-case', 'value' => 'oidc.create-client']],
    ],
];