<?php

namespace App\Core\Routing;

use App\Core\Http\Request;

class RouteMatcher
{
    private $routes;

    public function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    public function match(Request $request): ?RouteMatched
    {
        // Implement the method to match a URL to a route
        foreach ($this->routes->getAll() as $route) {
            $routeMatched = $route->match($request);
            if ($routeMatched) {
                return $routeMatched;
            }
        }
        return null;
    }
}