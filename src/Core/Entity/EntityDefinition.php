<?php

namespace App\Core\Entity;

class EntityDefinition
{
    private $_definitions = [];

    public function register($name, $definition)
    {
        $this->_definitions[$name] = $definition;
    }

    public function get($name)
    {
        if (!isset($this->_definitions[$name])) {
            throw new \Exception("Entity $name not found in EntityDefinition.");
        }
        return $this->_definitions[$name];
    }
}