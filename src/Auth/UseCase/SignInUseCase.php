<?php

namespace App\Auth\UseCase;

use App\Auth\Authentication\Authenticator;
use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\User\Password\PasswordEncoder;
use App\User\Repository\UserRepositoryInterface;

class SignInUseCase implements UseCaseInterface
{
    private UserRepositoryInterface $userRepository;
    private PasswordEncoder $passwordEncoder;
    private Authenticator $authenticator;

    function __construct(EntityManager $entityManager, PasswordEncoder $passwordEncoder, Authenticator $authenticator)
    {
        $this->userRepository = $entityManager->getRepository('user');
        $this->passwordEncoder = $passwordEncoder;
        $this->authenticator = $authenticator;
    }

    public function execute(array $params = [])
    {

        $email = $params['identity'] ?? null;
        $password = $params['password'] ?? null;

        if (!$email || !$password) {
            throw new \InvalidArgumentException('Identity and password are required.');
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->passwordEncoder->verify($password, $user->password)) {
            throw new \RuntimeException('Invalid email or password.');
        }

        $this->authenticator->login($user, 'local');

        return $user;

    }
}