<?php

use App\Core\Di\Locator;
return [
    'parameters' => [
        'factory' => [App\Core\Config\ParametersFactory::class, 'create'],
    ],
    'db' => [
        'factory' => [App\Core\Db\DbFactory::class, 'create'],
        'arguments' => ['@parameters'],
    ],
    'session' => [
        'factory' => [App\System\Session\SessionFactory::class, 'create'],
    ],
    'route.matcher' => [
        'class' => App\Core\Routing\RouteMatcher::class,
        'arguments' => ['@routes'],
    ],
    'route.generator' => [
        'class' => App\Core\Routing\RouteGenerator::class,
        'arguments' => ['@routes'],
    ],
    'routes' => [
        'factory' => [App\Core\Routing\RoutesFactory::class, 'create'],
        'arguments' => ['@route.loader'],
    ],
    'route.loader' => [
        'class' => App\Core\Routing\RouteLoader::class,
    ],
    'controller' => [
        'class' => App\Core\Di\Locator::class,
        'arguments' => ['controller']
    ],
    'controller.helpers' => [
        'class' => App\Core\Di\Locator::class,
        'arguments' => ['controller.helper'],
    ],
    'controller.helper.csrf_token' => [
        'class' => App\Core\Mvc\Controller\Helper\CsrfToken::class,
        'arguments' => ['@session'],
        'tags' => [['name' => 'controller.helper', 'value' => 'csrf-token']]
    ],
    'controller.helper.translator' => [
        'class' => App\Core\Mvc\Controller\Helper\Translator::class,
        'arguments' => ['@translator'],
        'tags' => [['name' => 'controller.helper', 'value' => 'translate']]
    ],
    'view' => [
        'factory' => [App\Core\Mvc\View\ViewFactory::class, 'create'],
        'arguments' => ['@view.helper'],
    ],
    'view.helper' => [
        'class' => App\Core\Di\Locator::class,
        'arguments' => ['view.helper'],
    ],
    'view.helper.layout' => [
        'class' => App\Core\Mvc\View\Helper\Layout::class,
        'tags' => [['name' => 'view.helper', 'value' => 'layout']],
    ],
    'view.helper.vite' => [
        'class' => App\Core\Mvc\View\Helper\Vite::class,
        'tags' => [['name' => 'view.helper', 'value' => 'vite']],
    ],
    'view.helper.route' => [
        'class' => App\Core\Mvc\View\Helper\Route::class,
        'arguments' => ['@route.generator'],
        'tags' => [['name' => 'view.helper', 'value' => 'route']],
    ],
    'view.helper.asset' => [
        'class' => App\Core\Mvc\View\Helper\Asset::class,
        'tags' => [['name' => 'view.helper', 'value' => 'asset']],
    ],
    'view.helper.translator' => [
        'class' => App\Core\Mvc\View\Helper\Translator::class,
        'arguments' => ['@translator'],
        'tags' => [['name' => 'view.helper', 'value' => 'translate']],
    ],
    'translator' => [
        'class' => App\Core\Translation\Translator::class,
    ],
    'use-case-bus' => [
        'class' => \App\Core\UseCase\UseCaseBus::class
    ],
    'entity.definition' => [
        'factory' => [\App\Core\Entity\EntityDefinitionFactory::class, 'create'],
    ],
    'entity.manager' => [
        'class' => \App\Core\Entity\EntityManager::class,
        'arguments' => ['@entity.definition', '@db', '@entity.repository']
    ],
    'entity.repository' => [
        'class' => \App\Core\Entity\EntityRepositoryLocator::class,
    ],
    'mailer' => [
        'class' => \App\Core\Mail\Mailer::class,
        'arguments' => ['@view', '@parameters']
    ],
    'helper.string' => [
        'class' => \App\Core\Helper\StringHelper::class,
    ],
    'watchdog' => [
        'class' => \App\Core\Watchdog\WatchdogService::class,
        'arguments' => ['@watchdog.storage.db', '!watchdog.levels'],
    ],
    'watchdog.storage.db' => [
        'class' => \App\Core\Watchdog\Storage\DbStorage::class,
        'arguments' => ['@db'],
    ],
];