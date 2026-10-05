<?php

namespace App\Auth\Authentication;

use App\Auth\Session\AuthBag;
use App\Core\Entity\EntityManager;
use App\Core\Session\Session;
use App\User\Repository\UserRepositoryInterface;
use App\User\Password\PasswordEncoder;

class AuthenticationService
{
    private readonly UserRepositoryInterface $userRepository;
    private readonly PasswordEncoder $passwordEncoder;
    private Identity $identity;
    private Session $session;

    private $loaded = false;

    public function __construct(
        EntityManager $entityManager,
        PasswordEncoder $passwordEncoder,
        Session $session
    ) {
        $userRepository = $entityManager->getRepository('user');
        if ($userRepository instanceof UserRepositoryInterface) {
            $this->userRepository = $userRepository;
        } else {
            throw new \RuntimeException('User repository must implement UserRepositoryInterface.');
        }
        $this->passwordEncoder = $passwordEncoder;
        $this->session = $session;
    }

    public function isLoggedIn(): bool
    {
        if (!$this->loaded) {
            $this->getIdentity();
        }
        return $this->identity->getId() !==  '';
    }

    public function getIdentity(): Identity
    {
        if (!$this->loaded) {
            $userId = $this->getAuthBag()->get('user_id', '');
            $this->identity = new Identity($userId);
            $this->loaded = true;
        }

        return $this->identity;
    }

    public function validate(string $email, string $password): AuthenticationResult
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return new AuthenticationResult(false, null, AuthenticationResult::ERROR_USER_NOT_FOUND);
        }

        if (!$this->passwordEncoder->verify($password, $user->password_hash)) {
            return new AuthenticationResult(false, null, AuthenticationResult::ERROR_INVALID_PASSWORD);
        }

        return new AuthenticationResult(true, $user);
    }

    public function login(string $userId, $type = 'local', array $data = []): void
    {
        $authBag = $this->getAuthBag();
        $authBag->set('user_id', $userId);
        $authBag->set('login_type', $type);
        $authBag->set('login_data', $data);
        $this->identity = new Identity($userId);
        $this->loaded = true;
    }

    public function logout(): void
    {
        $authBag = $this->getAuthBag();
        $authBag->remove('user_id');
        $authBag->remove('login_type');
        $authBag->remove('login_data');
        $this->identity = new Identity('');
        $this->loaded = true;
    }

    private function getAuthBag(): AuthBag
    {
        return $this->session->getBag('auth');
    }
}
