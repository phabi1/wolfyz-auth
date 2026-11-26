<?php

namespace App\Auth\UseCase;

use App\Auth\Exception\EmailAlreadyExistsException;
use App\Auth\Exception\InvalidRegistrationException;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Entity\Exception\DuplicateEntityException;
use App\Core\UseCase\UseCaseInterface;

class RegisterUserUseCase implements UseCaseInterface
{
    private const PASSWORD_MIN_LENGTH = 8;

    private readonly EntityRepositoryInterface $userRepository;

    private readonly EntityRepositoryInterface $authUserProvider;

    public function __construct(EntityManager $entityManager)
    {
        $this->userRepository = $entityManager->getRepository('user');
        $this->authUserProvider = $entityManager->getRepository('auth-user-provider');
    }

    public function execute(array $params = []): \stdClass
    {
        $params = $this->validateUser($params);

        return $this->registerUser($params);
    }

    private function validateUser(array $params): array
    {
        $email = trim((string) ($params['email'] ?? ''));
        $password = (string) ($params['password'] ?? '');
        $firstname = trim((string) ($params['firstname'] ?? ''));
        $lastname = trim((string) ($params['lastname'] ?? ''));

        if ($firstname === '' || $lastname === '') {
            throw new InvalidRegistrationException('Firstname and lastname are required.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidRegistrationException('Invalid email address.');
        }
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new InvalidRegistrationException('Password must be at least 8 characters.');
        }

        return array_merge($params, [
            'email' => $email,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]);
    }

    private function registerUser(array $params): \stdClass
    {
        if ($this->userRepository->findOne(['email' => ['eq' => $params['email']]])) {
            throw new EmailAlreadyExistsException($params['email']);
        }

        try {
            // The repository hashes password_hash before persisting it.
            $user = $this->userRepository->insert([
                'id' => bin2hex(random_bytes(16)),
                'email' => $params['email'],
                'password_hash' => $params['password'],
                'name' => trim($params['firstname'] . ' ' . $params['lastname']),
                'firstname' => $params['firstname'],
                'lastname' => $params['lastname'],
            ]);
        } catch (DuplicateEntityException) {
            throw new EmailAlreadyExistsException($params['email']);
        }

        $this->authUserProvider->insert([
            'user_id' => $user->id,
            'auth_provider_id' => $params['provider'] ?? 'local',
            'value' => $params['provider_value'] ?? $params['email'],
        ]);

        return $user;
    }
}
