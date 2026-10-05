<?php

namespace App\Core\Routing;

class RoutesFactory
{
    public static function create(RouteLoader $loader)
    {
        $routes = $loader->load();
        $collection = new Routes();
        $collection->setRoutes($routes);
        return $collection;
    }
}