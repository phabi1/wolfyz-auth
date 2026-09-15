<?php

namespace App\Core\Entity;

use App\Core\Db\Db;
use App\Core\Di\ContainerAwareInterface;

class EntityManager implements ContainerAwareInterface {

    private $container;

    private $entityDefinition;

    private $db;

    private $repositories = [];
    
    private EntityRepositoryLocator $locator;

    public function __construct(
        EntityDefinition $entityDefinition,
        Db $db,
        EntityRepositoryLocator $locator
    ) {
        $this->entityDefinition = $entityDefinition;
        $this->db = $db;
        $this->locator = $locator;
    }

    public function setContainer($container) {
        $this->container = $container;
    }

    public function getEntityDefinition() {
        return $this->entityDefinition;
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
        $id = $definition->getRepository() ?? null;
        $repository = $this->locator->get($id);
        $repository->setDefinition($definition);
        $repository->setDb($this->db);
        $this->repositories[$entityName] = $repository;
        return $repository;
        
    }
}