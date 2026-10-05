<?php

namespace App\Oidc\Server\Repository;

use App\Core\Db\Db;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Oidc\Server\Entity\AuthCodeEntity;
use App\Oidc\Server\NonceContext;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    private readonly EntityRepositoryInterface $authCodeEntityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->authCodeEntityRepository = $entityManager->getRepository('oidc-auth-code');
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $scopes = implode(' ', array_map(fn ($scope) => $scope->getIdentifier(), $authCodeEntity->getScopes()));

        $this->authCodeEntityRepository->insert([
                'id' => $authCodeEntity->getIdentifier(),
                'client_id' => $authCodeEntity->getClient()->getIdentifier(),
                'user_id' => $authCodeEntity->getUserIdentifier(),
                'scopes' => $scopes,
                'redirect_uri' => $authCodeEntity->getRedirectUri(),
                'nonce' => NonceContext::get(),
                'expires_at' => $authCodeEntity->getExpiryDateTime()->getTimestamp(),
            ]
        );
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->authCodeEntityRepository->update($codeId, ['revoked' => 1]);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        $row = $this->authCodeEntityRepository->findOne(['id' => ['eq' => $codeId]]);

        if (!$row) {
            return true;
        }

        // Make the nonce available to AccessTokenRepository::persistNewAccessToken(),
        // which runs immediately afterwards within the same /token request.
        NonceContext::set($row->nonce);

        return (bool) $row->revoked;
    }
}
