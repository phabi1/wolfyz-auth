<?php

namespace App\Core\Routing;

use App\Core\Routing\Route\RouteInterface;

class Routes
{
    private $routes = [];

    public function setRoutes(array $routes): void
    {
        $this->routes = [];
        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    public function add(RouteInterface $route): void
    {
        $name = $route->getName();
        $this->routes[$name] = $route;
    }

    public function get($name): ?RouteInterface
    {
        return $this->routes[$name] ?? null;
    }

    public function getAll(): array
    {
        return $this->routes;
    }
}