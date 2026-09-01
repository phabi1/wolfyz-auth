<?php

namespace App\Core\Mvc\View\Helper;

class Route {
    private$urlGenerator;

    public function __construct($urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function __invoke($name, $parameters = []) {
        return $this->urlGenerator->generate($name, $parameters);
    }
}