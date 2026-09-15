<?php

namespace App\Core\Di;

class Locator implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $_tag = null;

    public function __construct($tag)
    {
        $this->_tag = $tag;
    }

    public function has(string $key): bool
    {
        $definitionIds = $this->container->findByTag($this->_tag);
        foreach ($definitionIds as $serviceName) {
            $def = $this->container->getDefinition($serviceName);
            foreach ($def['tags'] as $tag) {
                if ($tag['name'] === $this->_tag && $tag['value'] === $key) {
                    return true;
                }
            }
        }
        return false;
    }

    public function get(string $key): mixed
    {
        $definitionIds = $this->container->findByTag($this->_tag);
        foreach ($definitionIds as $serviceName) {
            $def = $this->container->getDefinition($serviceName);
            foreach ($def['tags'] as $tag) {
                if ($tag['name'] === $this->_tag && $tag['value'] === $key) {
                    return $this->container->get($serviceName);
                }
            }
        }
        throw new \Exception("Service with name $key not found.");
    }
}