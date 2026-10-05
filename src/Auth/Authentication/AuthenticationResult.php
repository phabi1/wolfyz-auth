<?php

namespace App\Auth\Authentication;

class AuthenticationResult
{
    const ERROR_USER_NOT_FOUND = 'user_not_found';
    const ERROR_INVALID_PASSWORD = 'invalid_password';

    public readonly bool $valid;
    public readonly ?object $user;

    public readonly ?string $error;

    public function __construct(bool $valid, ?object $user = null, ?string $error = null)
    {
        $this->valid = $valid;
        $this->user = $user;
        $this->error = $error;
    }
}