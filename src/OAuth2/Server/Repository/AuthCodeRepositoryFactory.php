<?php

namespace App\OAuth2\Server\Repository;

use App\Core\Entity\EntityManager;

class AuthCodeRepositoryFactory
{
    public static function create(EntityManager $entityManager)
    {
        return new AuthCodeRepository($entityManager->getRepository('oauth2.auth_code'));
    }
}