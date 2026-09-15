<?php

namespace App\OAuth2\Server\Repository;

use App\Core\Entity\EntityManager;

class RefreshTokenRepositoryFactory
{
    public static function create(EntityManager $entityManager): RefreshTokenRepository
    {
        return new RefreshTokenRepository($entityManager->getRepository('oauth2.refresh_token'));
    }
}