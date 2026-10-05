<?php

namespace App\Auth\UseCase;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\UseCase\UseCaseInterface;
use App\Auth\Exception\EmailAlreadyExistsException;
use App\Core\Exception\DuplicateEntryException;

class RegisterUseUseCase implements UseCaseInterface
{
    private readonly EntityRepositoryInterface $userRepository;

    private readonly EntityRepositoryInterface $authUserProvider;

    public function __construct(EntityManager $entityManager)
    {
        $this->userRepository = $entityManager->getRepository('user');
        $this->authUserProvider = $entityManager->getRepository('auth-user-provider');
    }

    public function execute(array $params = []): void
    {
        $this->validateUser($params);

        $this->registerUser($params);
    }

    private function validateUser(array $params): void
    {
        // Implement the user validation logic here
    }

    private function registerUser(array $params): void
    {
        try {
            $user = $this->userRepository->insert(
                [
                    'email' => $params['email'],
                    'password_hash' => $params['password'],
                    'firstname' => $params['firstname'],
                    'lastname' => $params['lastname'],
                ]
            );

            $provider = $params['provider'] ?? 'local';
            $value = $params['provider_value'] ?? $params['email'];

            $this->authUserProvider->insert(
                [
                    'user_id' => $user->id,
                    'auth_provider_id' => $provider,
                    'value' => $value,
                ]
            );
        } catch (DuplicateEntryException) {
            throw new EmailAlreadyExistsException($params['email']);
        }
    }
}