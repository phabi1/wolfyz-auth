<?php

namespace App\Auth\Exception;

use Exception;

class EmailAlreadyExistsException extends Exception
{
    private string $email;

    public function __construct(string $email )
    {
        $this->email = $email;
        parent::__construct("An account with this email already exists.");
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}