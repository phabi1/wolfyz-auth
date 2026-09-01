<?php

namespace App\Core\Mvc\Router;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesFactory
{
    public static function create()
    {
        $routes = require APP_DIR . '/config/routes.php';
        $collection = new RouteCollection();
        foreach ($routes as $name => $info) {
            $collection->add($name, new Route(
                $info['path'],
                $info['controller'],
                $info['requirements'] ?? [],
                $info['options'] ?? [],
                $info['host'] ?? '',
                $info['schemes'] ?? [],
                $info['methods'] ?? [],
                $info['condition'] ?? ''
            ));
        }
        return $collection;
    }
}