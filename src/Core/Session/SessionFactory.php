<?php

namespace App\Core\Session;

use Symfony\Component\HttpFoundation\Session\Session;

class SessionFactory {
    public static function create()
    {
        return new Session();
    }
}