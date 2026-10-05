<?php

namespace App\Core\Config;

class ParametersFactory
{
    public static function create(): Parameters
    {
        $parameters = new Parameters();
        $parameters->load(CONFIG_DIR . '/parameters.' . APP_ENV . '.php');
        return $parameters;
    }
}
