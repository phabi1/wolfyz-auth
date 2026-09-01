<?php

namespace App\OAuth2\Repository;

use App\Core\Entity\EntityRepositoryInterface;
use App\OAuth2\Entity\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    private EntityRepositoryInterface $repository;

    public function __construct(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // Scopes valides enregistrés dans l'application
    private array $definedScopes = [
        'openid' => 'Permission d\'accès à l\'identifiant OpenID',
        'profile' => 'Permission d\'accès au profil',
        'email' => 'Permission d\'accès à l\'email'
    ];

    public function getScopeEntityByIdentifier($scopeIdentifier): ?ScopeEntityInterface
    {
        if (!array_key_exists($scopeIdentifier, $this->definedScopes)) {
            return null;
        }

        return new ScopeEntity($scopeIdentifier);
    }

    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        // Possibilité de filtrer ou restreindre les scopes selon le type de grant,
        // le client ou l'utilisateur connecté.
        
        // Exemple : si aucun scope demandé, attribuer un scope par défaut
        if (empty($scopes)) {
            $scopes[] = new ScopeEntity('read');
        }

        return $scopes;
    }
}