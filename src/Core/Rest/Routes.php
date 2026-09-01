<?php

namespace App\Core\Rest;

class Routes
{
    const ROUTE_ITEMS = 1;
    const ROUTE_ITEM = 2;
    const ROUTE_CREATE = 4;
    const ROUTE_UPDATE = 8;
    const ROUTE_DELETE = 16;
    const ROUTE_BULK_CREATE = 32;
    const ROUTE_BULK_UPDATE = 64;
    const ROUTE_BULK_DELETE = 128;
    const ROUTE_ALL = 255;
    
    public static function create($entity, $path, $controller, $options = [])
    {
        $options += [
            'actions' => self::ROUTE_ALL
        ];

        $routes = [];

        if ($options['actions'] & self::ROUTE_ITEMS) {
            $routes[$entity . '-items'] = [
                'path' => $path,
                'methods' => ['GET'],
                'controller' => [$controller, 'items']
            ];
        }

        if ($options['actions'] & self::ROUTE_ITEM) {
            $routes[$entity . '-item'] = [
                'path' => $path . '/{id}',
                'methods' => ['GET'],
                'controller' => [$controller, 'item'],
                'requirements' => ['id' => '[0-9]+'],
            ];
        }

        if ($options['actions'] & self::ROUTE_CREATE) {
            $routes[$entity . '-create'] = [
                'path' => $path,
                'methods' => ['POST'],
                'controller' => [$controller, 'create']
            ];
        }

        if ($options['actions'] & self::ROUTE_UPDATE) {
            $routes[$entity . '-update'] = [
                'path' => $path . '/{id}',
                'methods' => ['PUT'],
                'controller' => [$controller, 'update']
            ];
        }

        if ($options['actions'] & self::ROUTE_DELETE) {
            $routes[$entity . '-delete'] = [
                'path' => $path . '/{id}',
                'methods' => ['DELETE'],
                'controller' => [$controller, 'delete']
            ];
        }

        if ($options['actions'] & self::ROUTE_BULK_CREATE) {
            $routes[$entity . '-bulk_create'] = [
                'path' => $path . '/bulk',
                'methods' => ['POST'],
                'controller' => [$controller, 'bulkCreate']
            ];
        }

        if ($options['actions'] & self::ROUTE_BULK_UPDATE) {
            $routes[$entity . '-bulk_update'] = [
                'path' => $path . '/bulk',
                'methods' => ['PUT'],
                'controller' => [$controller, 'bulkUpdate']
            ];
        }

        if ($options['actions'] & self::ROUTE_BULK_DELETE) {
            $routes[$entity . '-bulk_delete'] = [
                'path' => $path . '/bulk',
                'methods' => ['DELETE'],
                'controller' => [$controller, 'bulkDelete']
            ];
        }
        return $routes;
    }
}