<?php

namespace App\OAuth2\Server\Repository;

use App\Core\Entity\EntityManager;

class ClientRepositoryFactory
{
    public static function create(EntityManager $entityManager): ClientRepository
    {
        return new ClientRepository($entityManager->getRepository('oauth2.client'));
    }
}