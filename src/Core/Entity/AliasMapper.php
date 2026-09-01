<?php

namespace App\Core\Entity;

class AliasMapper
{
    private $aliasMap = [];

    private $counter = 0;

    public function addAlias($entityName)
    {
        $this->counter++;
        $this->aliasMap[$entityName] = 'a' . $this->counter;
        return $this->aliasMap[$entityName];
    }

    public function getAlias($entityName, $createIfNotExists = true)
    {
        if (!isset($this->aliasMap[$entityName]) && $createIfNotExists) {
            $this->aliasMap[$entityName] = $this->addAlias($entityName);
        }
        return $this->aliasMap[$entityName] ?? null;
    }
}