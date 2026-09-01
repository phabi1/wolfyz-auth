<?php

namespace App\OAuth2\Repository;

use App\Core\Db\Db;
use App\Core\Entity\EntityRepositoryInterface;
use App\OAuth2\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private EntityRepositoryInterface $repository;

    public function __construct(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $this->repository->insert([
            'access_token' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'refresh_token' => $refreshTokenEntity->getIdentifier(),
            'client_id' => $refreshTokenEntity->getAccessToken()->getClient()->getIdentifier(),
            'expires' => $refreshTokenEntity->getExpiryDateTime()->getTimestamp() + (60 * 60 * 2),
            'scope' => implode(' ', array_map(fn($scope) => $scope->getIdentifier(), $refreshTokenEntity->getAccessToken()->getScopes()))
        ]);
    }

    public function revokeRefreshToken($tokenId): void
    {
        $this->repository->deleteBy(['refresh_token' => ['eq' => $tokenId]]);
    }

    public function isRefreshTokenRevoked($tokenId): bool
    {
        $row = $this->repository->findOne(['refresh_token' => ['eq' => $tokenId]]);

        return $row ? false : true; // Token actif par défaut pour l'exemple
    }
}