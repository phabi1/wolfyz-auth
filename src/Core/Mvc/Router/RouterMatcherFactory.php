<?php

namespace App\Core\Mvc\Router;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouterMatcherFactory
{
    public static function create(RouteCollection $routes, RequestContext $context)
    {
        $routeMatcher = new UrlMatcher($routes, $context);
        return $routeMatcher;
    }
}