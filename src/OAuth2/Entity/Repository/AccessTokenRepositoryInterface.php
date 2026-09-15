<?php

namespace App\OAuth2\Entity\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface AccessTokenRepositoryInterface extends EntityRepositoryInterface
{
    public function findByToken(string $token): ?\stdClass;
}