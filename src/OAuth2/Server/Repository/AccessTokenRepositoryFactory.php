<?php

namespace App\OAuth2\Server\Repository;

use App\Core\Entity\EntityManager;

class AccessTokenRepositoryFactory
{
    public static function create(EntityManager $entityManager): AccessTokenRepository
    {
        return new AccessTokenRepository($entityManager->getRepository('oauth2.access_token'));
    }
}