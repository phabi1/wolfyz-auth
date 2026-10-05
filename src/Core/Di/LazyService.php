<?php

namespace App\Core\Di;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;

class LazyService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private string $id;
    private mixed $instance = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __invoke()
    {
        if ($this->instance === null) {
            $this->instance = $this->container->get($this->id);
        }
        return $this->instance;
    }
}