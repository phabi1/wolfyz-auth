<?php

namespace App\Core\Entity;

use App\Core\Db\Db;

class EntityManager {

    private $entityDefinition;
    private $db;

    private $repositories = [];

    public function __construct(
        EntityDefinition $entityDefinition,
        Db $db
    ) {
        $this->entityDefinition = $entityDefinition;
        $this->db = $db;
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getRepository($entityName): EntityRepositoryInterface
    {
        if (isset($this->repositories[$entityName])) {
            return $this->repositories[$entityName];
        }
        $definition = $this->entityDefinition->get($entityName);
        if (!$definition) {
            throw new \Exception("Entity $entityName not found.");
        }
        $class = $definition['repository'] ?? null;
        if (!$class) {
            $repository = new EntityRepository($this->db, $definition);
            return $this->repositories[$entityName] = $repository;
        }
        return $this->repositories[$entityName] = new $class($this->db, $definition);
    }
}