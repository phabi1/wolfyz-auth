<?php

namespace App\Core\Config;

class Parameters
{
    private $_parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->_parameters = $parameters;
    }

    public function load($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Parameters file not found: $path");
        }
        $parameters = require $path;
        $this->_parameters = $parameters;
    }

    public function get(string $key, $default = null)
    {
        $path = explode('.', $key);
        $value = $this->_parameters;
        foreach ($path as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}