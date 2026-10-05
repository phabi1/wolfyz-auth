<?php

namespace App\Core\Kernel;

use App\Core\Di;

abstract class Base
{
    private $container;

    public function getContainer()
    {
        return $this->container;
    }

    public function bootstrap()
    {
        $this->setupDependencyInjection();

        $this->setupConfig();
    }

    private function setupDependencyInjection()
    {
        if (file_exists(CACHE_DIR . '/services.php')) {
            $definitions = require CACHE_DIR . '/services.php';
        } else {
            $definitions = require APP_DIR . '/config/services.php';
            file_put_contents(CACHE_DIR . '/services.php', '<?php return ' . var_export($definitions, true) . ';');
        }

        $container = new Di\Container($definitions);
        $this->container = $container;

    }

    private function setupConfig()
    {
        $env = APP_ENV;
        $this->container->get('parameters')->load(CONFIG_DIR . '/parameters.' . $env . '.php');
    }

    abstract public function run();
}