<?php

namespace App\OAuth2\Server\Repository;

use App\Core\Entity\EntityManager;

class ScopeRepositoryFactory
{
    public static function create(EntityManager $entityManager): ScopeRepository
    {
        return new ScopeRepository($entityManager->getRepository('oauth2.scope'));
    }
}