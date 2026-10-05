<?php

namespace App\Oidc\Server\Repository;

use App\Core\Entity\EntityManager;
use App\Oidc\Repository\ScopeEntityRepositoryInterface;
use App\Oidc\Repository\ClientEntityRepositoryInterface;
use App\Oidc\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    private readonly ScopeEntityRepositoryInterface $scopeEntityRepository;
    private readonly ClientEntityRepositoryInterface $clientEntityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $scopeEntityRepository = $entityManager->getRepository('oidc-scope');
        if (!$scopeEntityRepository instanceof ScopeEntityRepositoryInterface) {
            throw new \RuntimeException('Invalid repository for oidc-scope');
        }
        $this->scopeEntityRepository = $scopeEntityRepository;

        $clientEntityRepository = $entityManager->getRepository('oidc-client');
        if (!$clientEntityRepository instanceof ClientEntityRepositoryInterface) {
            throw new \RuntimeException('Invalid repository for oidc-client');
        }
        $this->clientEntityRepository = $clientEntityRepository;
    }

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        $row = $this->scopeEntityRepository->findById($identifier);

        return $row ? new ScopeEntity($row->name) : null;
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        // Restrict requested scopes to the ones registered for this client.
        $client = $this->clientEntityRepository->findByClientId($clientEntity->getIdentifier());
        $clientScopes = $client ? array_map('trim', explode(' ', $client->scopes)) : [];

        return array_values(array_filter(
            $scopes,
            fn (ScopeEntityInterface $scope) => in_array($scope->getIdentifier(), $clientScopes, true)
        ));
    }
}
