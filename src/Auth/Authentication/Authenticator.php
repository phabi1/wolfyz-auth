<?php

namespace App\Auth\Authentication;

use Symfony\Component\HttpFoundation\Session\Session;

class Authenticator
{
    private Session $session;

    private ?Identity $identity = null;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function isAuthenticated(): bool
    {
        return $this->session->has('auth_user_id');
    }

    public function getIdentity(): ?Identity
    {
        if ($this->identity === null && $this->isAuthenticated()) {
            $this->identity = new Identity($this->session->get('auth_user_id'));
        }

        return $this->identity;
    }

    public function login($user, string $provider = 'local')
    {
        // Implement login logic here, e.g., setting session data
        $this->session->set('auth_user_id', $user->id);
        $this->session->set('auth_provider', $provider);
        $this->identity = new Identity($user->id);
    }

    public function logout()
    {
        $this->session->remove('auth_user_id');
        $this->session->remove('auth_provider');
        $this->identity = null;
    }
}