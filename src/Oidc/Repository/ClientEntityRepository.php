<?php

namespace App\Oidc\Repository;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepository;

class ClientEntityRepository extends EntityRepository implements ClientEntityRepositoryInterface
{
    public function findByClientId(string $clientId)
    {
        return $this->findOne(['client_id' => ['eq' => $clientId]]);
    }
}