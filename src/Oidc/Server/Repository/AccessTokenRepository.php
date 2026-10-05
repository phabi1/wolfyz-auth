<?php

namespace App\Oidc\Server\Repository;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Oidc\Server\Entity\AccessTokenEntity;
use App\Oidc\Server\NonceContext;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    private readonly EntityRepositoryInterface $accessTokenEntityRepository;
    public function __construct(EntityManager $entityManager)
    {
        $this->accessTokenEntityRepository = $entityManager->getRepository('oidc-access-token');
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        if ($userIdentifier !== null) {
            $accessToken->setUserIdentifier($userIdentifier);
        }
        $accessToken->setNonce(NonceContext::get());

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $scopes = implode(' ', array_map(fn($scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes()));

        $this->accessTokenEntityRepository->insert([
            'id' => $accessTokenEntity->getIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'scopes' => $scopes,
            'expires_at' => $accessTokenEntity->getExpiryDateTime()->getTimestamp(),
        ]);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->accessTokenEntityRepository->update($tokenId, ['revoked' => 1]);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $row = $this->accessTokenEntityRepository->findOne(['id' => ['eq' => $tokenId]]);

        return !$row || (bool) $row->revoked;
    }
}
