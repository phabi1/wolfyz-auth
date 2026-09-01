<?php

namespace App\OAuth2\Repository;

use App\Core\Db\Db;
use App\Core\Entity\EntityManager;
use App\OAuth2\Repository\AccessTokenRepository;

class AccessTokenRepositoryFactory
{
    public static function create(EntityManager $entityManager): AccessTokenRepository
    {
        return new AccessTokenRepository($entityManager->getRepository('oauth2.access_token'));
    }
}