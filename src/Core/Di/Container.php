<?php

namespace App\Core\Di;

class Container
{
    private $definition = array();

    /**
     * @var array
     */
    private $services = array();

    public function __construct($definition = array())
    {
        $this->definition = $definition;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $service = $this->create($name);
        if (!isset($this->definition[$name]['shared']) || $this->definition[$name]['shared'] === true) {
            $this->services[$name] = $service;
        }
        return $service;
    }

    public function has(string $name)
    {
        return isset($this->definition[$name]);
    }

    public function getDefinition($name)
    {
        if (!isset($this->definition[$name])) {
            throw new \Exception("Service $name not found in container.");
        }
        return $this->definition[$name];
    }

    public function findByTag($tagName)
    {
        $services = [];
        foreach ($this->definition as $name => $def) {
            if (isset($def['tags'])) {
                foreach ($def['tags'] as $tag) {
                    if ($tag['name'] === $tagName) {
                        $services[] = $name;
                    }
                }
            }
        }
        return $services;
    }

    private function create($name)
    {
        if (!isset($this->definition[$name])) {
            throw new \Exception("Service $name not found in container.");
        }

        $definition = $this->definition[$name];
        if (isset($definition['class'])) {
            $class = $definition['class'];
            $args = isset($definition['arguments']) ? $definition['arguments'] : array();
            $resolvedArgs = $this->resolveArguments($args);
            $service = new $class(...$resolvedArgs);
        } else if (isset($definition['factory'])) {
            $factory = $definition['factory'];
            $args = isset($definition['arguments']) ? $definition['arguments'] : array();
            $resolvedArgs = $this->resolveArguments($args);
            $service = call_user_func_array($factory, $resolvedArgs);
        }

        if ($service instanceof ContainerAwareInterface) {
            $service->setContainer($this);
        }

        return $service;
    }

    private function resolveArguments($args)
    {
        $resolvedArgs = array();
        foreach ($args as $arg) {
            if (is_string($arg) && strpos($arg, '@') === 0) {
                $resolvedArgs[] = $this->get(substr($arg, 1));
            } else if (is_string($arg) && strpos($arg, '!') === 0) {
                $resolvedArgs[] = $this->getParameter(substr($arg, 1));
            } else {
                $resolvedArgs[] = $arg;
            }
        }
        return $resolvedArgs;
    }

    private function getParameter($name)
    {
        return $this->get('parameters')->get($name);
    }
}