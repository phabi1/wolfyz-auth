<?php

namespace App\OAuth2\Repository;

use App\Core\Entity\EntityManager;
use App\OAuth2\Repository\AuthCodeRepository;

class AuthCodeRepositoryFactory
{
    public static function create(EntityManager $entityManager)
    {
        return new AuthCodeRepository($entityManager->getRepository('oauth2.auth_code'));
    }
}