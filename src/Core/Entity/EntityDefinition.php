<?php

namespace App\Core\Entity;

use App\Core\Entity\Definition\Definition;

class EntityDefinition
{
    private $_definitions = [];

    public function register($name, $definition)
    {
        $definition['name'] = $name;
        $this->_definitions[$name] = new Definition($definition);
      
    }

    public function get($name)
    {
        if (!isset($this->_definitions[$name])) {
            throw new \Exception("Entity $name not found in EntityDefinition.");
        }
        return $this->_definitions[$name];
    }
}