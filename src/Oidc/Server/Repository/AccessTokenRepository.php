<?php

namespace App\Oidc\Server\Repository;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Oidc\Server\Entity\AccessTokenEntity;
use App\Oidc\Server\NonceContext;
use App\Oidc\Jwks;
use App\Oidc\Server\Entity\UserEntity;
use App\User\Role\RoleService;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    private readonly EntityRepositoryInterface $accessTokenEntityRepository;

    /**
     * @param RoleService|null $roles adds the user's permissions to the token
     * @param string|null $issuer "iss" claim of the token
     * @param string|null $publicKeyPath public key of the signing key ("kid" header)
     * @param UserRepository|null $users adds the user's email ("email" scope)
     */
    public function __construct(
        EntityManager $entityManager,
        private readonly ?RoleService $roles = null,
        private readonly ?string $issuer = null,
        private readonly ?Jwks $jwks = null,
        private readonly ?string $publicKeyPath = null,
        private readonly ?UserRepository $users = null,
    ) {
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
        if ($this->roles !== null) {
            // No user (client_credentials): the client acts on its own behalf.
            $accessToken->setPermissions($userIdentifier !== null
                ? $this->roles->userPermissions((string) $userIdentifier)
                : $this->roles->clientPermissions($clientEntity->getIdentifier()));
        }
        if ($userIdentifier !== null && $this->users !== null && $this->hasScope($scopes, 'email')) {
            $user = $this->users->getUserEntityByIdentifier((string) $userIdentifier);
            if ($user instanceof UserEntity && $user->email !== '') {
                $accessToken->setUserClaims(['email' => $user->email, 'email_verified' => $user->emailVerified]);
            }
        }
        $accessToken->setIssuer($this->issuer);
        if ($this->jwks !== null && $this->publicKeyPath !== null && is_file($this->publicKeyPath)) {
            $accessToken->setKeyId($this->jwks->keyId($this->publicKeyPath));
        }
        $accessToken->setNonce(NonceContext::get());

        return $accessToken;
    }

    /** @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes */
    private function hasScope(array $scopes, string $name): bool
    {
        foreach ($scopes as $scope) {
            if ($scope->getIdentifier() === $name) {
                return true;
            }
        }

        return false;
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
