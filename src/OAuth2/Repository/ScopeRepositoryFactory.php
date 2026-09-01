<?php

namespace App\OAuth2\Repository;

use App\Core\Entity\EntityManager;
use App\OAuth2\Repository\ScopeRepository;

class ScopeRepositoryFactory
{
    public static function create(EntityManager $entityManager): ScopeRepository
    {
        return new ScopeRepository($entityManager->getRepository('oauth2.scope'));
    }
}