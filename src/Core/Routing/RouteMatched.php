<?php

namespace App\Core\Routing;

use App\Core\Lib\Bag;

class RouteMatched
{
    public readonly Bag $parameters;

    public function __construct(
        public string $routeName,
        public string $controller,
        public string $action,
        array $parameters = []
    ) {
        $this->parameters = new Bag();
        foreach ($parameters as $key => $value) {
            $this->parameters->set($key, $value);
        }
    }
}