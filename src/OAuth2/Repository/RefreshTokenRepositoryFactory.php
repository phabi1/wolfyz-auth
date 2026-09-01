<?php

namespace App\OAuth2\Repository;

use App\Core\Entity\EntityManager;
use App\OAuth2\Repository\RefreshTokenRepository;

class RefreshTokenRepositoryFactory
{
    public static function create(EntityManager $entityManager): RefreshTokenRepository
    {
        return new RefreshTokenRepository($entityManager->getRepository('oauth2.refresh_token'));
    }
}