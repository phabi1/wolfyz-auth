<?php

namespace App\Core\Di;

interface ContainerAwareInterface
{
    public function setContainer(Container $container);
}