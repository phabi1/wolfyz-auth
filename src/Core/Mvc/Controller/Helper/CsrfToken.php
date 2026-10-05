<?php

namespace App\Core\Mvc\Controller\Helper;

use App\Core\Session\Session;

class CsrfToken
{
    private readonly Session $session;

    private $tokenName = 'csrf_token';

    public function __construct(Session $session) {
        $this->session = $session;
    }

    public function __invoke(): self {
        return $this;
    }

    public function generate(): string {
        if ($this->session->has($this->tokenName)) {
            return $this->session->get($this->tokenName);
        }
        $token = bin2hex(random_bytes(32));
        $this->session->set($this->tokenName, $token);
        return $token;
    }

    public function validate(string $token): bool {
        if (!$this->session->has($this->tokenName)) {
            return false;
        }
        $expected = $this->session->get($this->tokenName, '');
        return hash_equals($expected, $token);
    }
}