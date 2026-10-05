<?php

namespace App\System\Session;

use App\Core\Session\Session;

class SessionFactory
{
    public static function create(): Session
    {
        $session = new Session();
        $session->registerBag(new \App\Auth\Session\AuthBag());
        $session->start();
        return $session;
    }
}
