<?php

return [
    'oidc.command.create-client' => [
        'class' => App\Oidc\Command\CreateClientCommand::class,
        'arguments' => [
            '@use-case-bus',
        ],
        'tags' => [['name' => 'command', 'command' => 'oidc:create-client']],
    ]
];