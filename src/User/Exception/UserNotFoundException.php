<?php

namespace App\User\Exception;

class UserNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('User "%s" not found.', $id));
    }
}
