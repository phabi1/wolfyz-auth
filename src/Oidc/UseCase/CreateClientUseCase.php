<?php
namespace App\Oidc\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;

class CreateClientUseCase implements UseCaseInterface
{
    private EntityRepositoryInterface $clientRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->clientRepository = $entityManager->getRepository('oidc-client');
    }

    public function execute(array $params = []): \stdClass
    {
        $client_id = $params['client_id'] ?? null;
        $client_secret = $params['client_secret'] ?? null;
        $redirect_uris = $params['redirect_uris'] ?? [];
        $grant_types = $params['grant_types'] ?? [];
        $response_types = $params['response_types'] ?? '';
        $scopes = $params['scopes'] ?? '';

        $data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uris' => $redirect_uris,
            'grant_types' => $grant_types,
            'response_types' => $response_types,
            'scopes' => $scopes,
        ];

        $client = $this->clientRepository->insert($data);
        return $client;
    }
}