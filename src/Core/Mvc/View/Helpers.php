<?php

namespace App\Core\Mvc\View;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use App\Core\Mvc\View\Helper;

class Helpers implements ContainerAwareInterface
{
   use ContainerAwareTrait;

    private $types = [
        'vite' => Helper\Vite::class,
        'layout' => Helper\Layout::class,
        'translate' => 'view.helper.translator',
        'route' => 'view.helper.route'
    ];

    private $instances = [];

    public function has(string $type)
    {
        return isset($this->instances[$type]) || isset($this->types[$type]);
    }

    public function get(string $type)
    {
        if (!isset($this->instances[$type])) {
            $id = $this->types[$type] ?? null;
            if ($this->container->has($id)) {
                $instance = $this->container->get($id);
            } else if (class_exists($id)) {
                $instance = new $id();
            } else {
                throw new \RuntimeException("Unable to resolve helper for type '{$type}'");
            }
            $this->instances[$type] = $instance ?? null;
        }

        return $this->instances[$type] ?? null;
    }
}