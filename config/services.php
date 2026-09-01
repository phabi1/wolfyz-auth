<?php

return array_merge(
    [
        'parameters' => [
            'class' => \App\Core\Config\Parameters::class
        ],
        'db' => [
            'factory' => [\App\Core\Db\DbFactory::class, 'create'],
            'arguments' => ['@parameters']
        ],
        'session' => [
            'factory' => [\App\Core\Session\SessionFactory::class, 'create'],
        ],
        'routes' => [
            'factory' => [\App\Core\Mvc\Router\RoutesFactory::class, 'create'],
        ],
        'router-matcher' => [
            'factory' => [\App\Core\Mvc\Router\RouterMatcherFactory::class, 'create'],
            'arguments' => ['@routes', '@router-context']
        ],
        'router-generator' => [
            'factory' => [\App\Core\Mvc\Router\RouterGeneratorFactory::class, 'create'],
            'arguments' => ['@routes', '@router-context']
        ],
        'router-context' => [
            'factory' => [\App\Core\Mvc\Router\RouterContextFactory::class, 'create'],
        ],
        'view' => [
            'factory' => [\App\Core\Mvc\View\ViewFactory::class, 'create'],
            'arguments' => ['@view.helpers']
        ],
        'view.helpers' => [
            'class' => \App\Core\Mvc\View\Helpers::class
        ],
        'use-case-bus' => [
            'class' => \App\Core\UseCase\UseCaseBus::class
        ],
        'entity-definition' => [
            'factory' => [\App\Core\Entity\EntityDefinitionFactory::class, 'create'],
        ],
        'entity-manager' => [
            'class' => \App\Core\Entity\EntityManager::class,
            'arguments' => ['@entity-definition', '@db']
        ],
        'translator' => [
            'class' => \App\Core\Translation\Translator::class
        ],
        'view.helper.translator' => [
            'class' => \App\Core\Mvc\View\Helper\Translator::class,
            'arguments' => ['@translator']
        ],
        'view.helper.route' => [
            'class' => \App\Core\Mvc\View\Helper\Route::class,
            'arguments' => ['@router-generator']
        ],
    ],
    require __DIR__ . '/services/auth.php',
    require __DIR__ . '/services/user.php',
    require __DIR__ . '/services/oauth2.php'
);