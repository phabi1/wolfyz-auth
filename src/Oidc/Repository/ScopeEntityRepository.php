<?php

namespace App\Oidc\Repository;

use App\Core\Entity\EntityRepository;
use App\Oidc\Repository\ScopeEntityRepositoryInterface;

class ScopeEntityRepository extends EntityRepository implements ScopeEntityRepositoryInterface
{
    public function findByName(string $name)
    {
        return $this->findOne(['name' => ['eq' => $name]]);
    }
}