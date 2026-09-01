<?php

namespace App\Core\Mvc\Router;

use Symfony\Component\Routing\RequestContext;

class RouterContextFactory
{
    public static function create()
    {
        return new RequestContext();
    }
}