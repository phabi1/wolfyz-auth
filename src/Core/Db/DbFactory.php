<?php

namespace App\Core\Db;

use App\Core\Config\Parameters;
use \App\Core\Db\Db as Client;
use App\Core\Db\Handler\PdoHandler;

class DbFactory
{
    public static function create(Parameters $parameters)
    {
        $dbConfig = $parameters->get('db', []);

        $handler = new PdoHandler($dbConfig['dsn'] ?? '', $dbConfig['username'] ?? '', $dbConfig['password'] ?? '');
        $instance = new Client($handler);
        return $instance;
    }
}