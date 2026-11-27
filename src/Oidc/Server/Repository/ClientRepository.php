<?php

namespace App\Oidc\Server\Repository;

use App\Core\Entity\EntityManager;
use App\Oidc\Repository\ClientEntityRepositoryInterface;
use App\Oidc\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    private ClientEntityRepositoryInterface $clientEntityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $clientEntityRepository = $entityManager->getRepository('oidc-client');
        if (!$clientEntityRepository instanceof ClientEntityRepositoryInterface) {
            throw new \RuntimeException('Invalid repository for oidc-client');
        }
        $this->clientEntityRepository = $clientEntityRepository;
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $row = $this->clientEntityRepository->findByClientId($clientIdentifier);

        if (!$row) {
            return null;
        }

        // A client with a secret is confidential: it must authenticate on the token
        // endpoint. A client without one is public (SPA, mobile) and relies on PKCE.
        $isConfidential = !empty($row->client_secret);

        return new ClientEntity(
            $row->client_id,
            $row->client_id,
            $row->redirect_uris,
            $isConfidential,
            $row->grant_types,
        );
    }

    /**
     * Only called by league/oauth2-server for confidential clients.
     * client_secret is stored as a password_hash() hash.
     */
    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $row = $this->clientEntityRepository->findByClientId($clientIdentifier);

        if (!$row || empty($row->client_secret)) {
            return false;
        }

        if ($grantType !== null && !in_array($grantType, $row->grant_types, true)) {
            return false;
        }

        return $clientSecret !== null && $clientSecret !== ''
            && password_verify($clientSecret, $row->client_secret);
    }
}
