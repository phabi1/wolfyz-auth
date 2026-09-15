<?php

namespace App\Core\Entity;

use App\Core\Di\Locator;

class EntityRepositoryLocator extends Locator
{
    private $db;
    private $entityDefinition;

    public function __construct()
    {
        parent::__construct('entity.repository');
    }

    public function get(?string $id): mixed
    {
        if ($id === null) {
            $repository = new EntityRepository();
        } else if ($this->has($id)) {
            $repository = parent::get($id);
        } else if (class_exists($id)) {
            $repository = new $id();
        } else {
            $repository = new EntityRepository();
        }
        return $repository;
    }
}