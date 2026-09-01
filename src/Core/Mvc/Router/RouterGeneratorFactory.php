<?php

namespace App\Core\Mvc\Router;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouterGeneratorFactory
{
    public static function create(RouteCollection $routes, RequestContext $context)
    {
        $routeGenerator = new UrlGenerator($routes, $context);
        return $routeGenerator;
    }

    private static function loadRoutes()
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