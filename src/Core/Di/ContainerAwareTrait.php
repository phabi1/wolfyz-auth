<?php

namespace App\Core\Di;

trait ContainerAwareTrait
{
    protected $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}