<?php

namespace App\OAuth2\Repository;

use App\Core\Db\Db;
use App\Core\Entity\EntityRepositoryInterface;
use App\OAuth2\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    private EntityRepositoryInterface $repository;

    public function __construct(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $this->repository->insert([
            'code'      => $authCodeEntity->getIdentifier(),
            'user_id'    => $authCodeEntity->getUserIdentifier(),
            'client_id'  => $authCodeEntity->getClient()->getIdentifier(),
            'scope'     => implode(' ', array_map(fn($scope) => $scope->getIdentifier(), $authCodeEntity->getScopes())),
            'expires' => $authCodeEntity->getExpiryDateTime()->getTimestamp() + (60 * 60 * 2), // Adding 1 hour as an example
        ]);
    }

    public function revokeAuthCode($codeId): void
    {
        // $this->repository->deleteBy(['code' => ['eq' => $codeId]]);
    }

    public function isAuthCodeRevoked($codeId): bool
    {
        $row = $this->repository->findOne(['code' => ['eq' => $codeId]]);

        return $row ? false : true; // Code actif par défaut pour l'exemple
    }
}