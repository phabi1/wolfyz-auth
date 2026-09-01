<?php

namespace App\OAuth2\Repository;

use App\Core\Entity\EntityRepositoryInterface;
use App\OAuth2\Entity\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    private EntityRepositoryInterface $repository;

    public function __construct(EntityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        $client = $this->repository->findOne(['client_id' => ['eq' => $clientIdentifier]]);
        
        if (!$client) {
            return null;
        }

        return new ClientEntity([
            'identifier' => $client->client_id,
            'name' => $client->title,
            'redirectUri' => $client->redirect_uri,
            'isConfidential' => in_array('client_credentials', $client->grant_types ?? []),
            'secret' => $client->client_secret,
        ]);
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $client = $this->getClientEntity($clientIdentifier);

        if ($client === null) {
            return false;
        }
        if ($client->isConfidential()) {
            if ($client instanceof ClientEntity && $client->getSecret() !== $clientSecret) {
                return false;
            }
        }
        return true;

    }
}