<?php

namespace App\Core\Routing;

use App\Core\Routing\Route\RouteFactory;
use App\Core\Routing\Route\RouteInterface;

class RouteLoader
{
    public function load(): array
    {
        $infos = require APP_DIR . '/config/routes.php';
        $routes = [];
        foreach ($infos as $name => $info) {
            $routes[] = $this->createRoute($name, $info);
        }
        return $routes;
    }

    private function createRoute(string $name, array $info): RouteInterface {
        return RouteFactory::create($name, $info);
    }
}