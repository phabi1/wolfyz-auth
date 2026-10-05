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

        $isConfidential = true;
        if (in_array('authorization_code', $row->grant_types)) {
            $isConfidential = false;
        }
    
        return new ClientEntity($row->client_id, $row->client_id, $row->redirect_uris, $isConfidential);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $row = $this->clientEntityRepository->findByClientId($clientIdentifier);

        if (!$row) {
            return false;
        }

        if (!in_array($grantType, $row->grant_types)) {
            return false;
        }

        if ($grantType === 'authorization_code') {
            return true;
        }

        return $clientSecret !== null && password_verify($clientSecret, $row->client_secret);
    }
}
