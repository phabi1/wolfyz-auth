<?php

namespace App\OAuth2\Server\Repository;

use App\Core\Entity\EntityRepositoryInterface;
use App\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{

    private EntityRepositoryInterface $repository;

    public function __construct(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null
    ): AccessTokenEntityInterface {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        if ($userIdentifier !== null) {
            $accessToken->setUserIdentifier($userIdentifier);
        }

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->repository->insert([
            'access_token' => $accessTokenEntity->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scope' => implode(' ', array_map(fn($scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes())),
            'revoked' => false,
            'expires' => $accessTokenEntity->getExpiryDateTime()->getTimestamp() + (60 * 60 * 2),
        ]);
    }

    public function revokeAccessToken($tokenId): void
    {
        // Exemple BDD : passer le flag `revoked` à true
        $this->repository->deleteBy(['access_token' => ['eq' => $tokenId]]);
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        $row = $this->repository->findOne(['access_token' => ['eq' => $tokenId]]);

        return $row ? false : true; // Token actif par défaut pour l'exemple
    }
}