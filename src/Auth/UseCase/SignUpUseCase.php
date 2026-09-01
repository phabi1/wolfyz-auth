<?php

namespace App\Auth\UseCase;

use App\Auth\Authentication\Authenticator;
use App\Core\Entity\EntityManager;
use App\Core\UseCase\UseCaseInterface;
use App\User\Password\PasswordEncoder;
use App\User\Repository\UserRepositoryInterface;

class SignUpUseCase implements UseCaseInterface
{
    private UserRepositoryInterface $userRepository;

    private Authenticator $authenticator;

    private PasswordEncoder $passwordEncoder;

    function __construct(EntityManager $entityManager, PasswordEncoder $passwordEncoder, Authenticator $authenticator)
    {
        $userRepository = $entityManager->getRepository('user');
        if ($userRepository instanceof UserRepositoryInterface === false) {
            throw new \InvalidArgumentException();
        }
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->authenticator = $authenticator;
    }

    public function execute(array $params = [])
    {
        $data = $params;
        $email = $params['email'] ?? null;
        $password = $params['password'] ?? null;
        if (!$email) {
            throw new \InvalidArgumentException('Email is required.');
        }
        if ($this->userRepository->existsEmail($email)) {
            throw new \InvalidArgumentException('Email already exists.');
        }
        if (!$password) {
            throw new \InvalidArgumentException('Password is required.');
        }
        $data['password'] = $this->passwordEncoder->encode($password);
        $user = $this->userRepository->insert($data);

        $this->authenticator->login($user, 'local');

        return $user;
    }
}