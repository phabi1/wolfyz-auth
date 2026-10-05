<?php

namespace App\Core\Mvc\View\Helper;

use App\Core\Routing\RouteGenerator;

class Route {
    private $generator;

    public function __construct(RouteGenerator $generator) {
        $this->generator = $generator;
    }

    public function __invoke($name, $parameters = []) {
        return $this->generator->generate($name, $parameters);
    }
}