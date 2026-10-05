<?php

namespace App\Oidc\Server\Repository;

use App\Core\Db\Db;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Oidc\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private readonly EntityRepositoryInterface $refreshTokenEntityRepository;
    
    public function __construct(EntityManager $entityManager)
    {
        $this->refreshTokenEntityRepository = $entityManager->getRepository('oidc-refresh-token');
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        
        $this->refreshTokenEntityRepository->insert([
            'id' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'expires_at' => $refreshTokenEntity->getExpiryDateTime()->getTimestamp(),
        ]);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $this->refreshTokenEntityRepository->update($tokenId, ['revoked' => 1]);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $row = $this->refreshTokenEntityRepository->findOne(['id' => ['eq' => $tokenId]]);

        return !$row || (bool) $row->revoked;
    }
}
