<?php

namespace App\OAuth2\Repository;

use App\Core\Entity\EntityManager;
use App\OAuth2\Repository\ClientRepository;

class ClientRepositoryFactory
{
    public static function create(EntityManager $entityManager): ClientRepository
    {
        return new ClientRepository($entityManager->getRepository('oauth2.client'));
    }
}