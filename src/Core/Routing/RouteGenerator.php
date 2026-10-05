<?php

namespace App\Core\Routing;

class RouteGenerator
{

    private $routes;

    public function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    public function generate($name, $parameters = [])
    {
        $route = $this->routes->get($name);
        if (!$route) {
            throw new \Exception("Route not found: " . $name);
        }
        return $route->assemble($parameters);
    }
}