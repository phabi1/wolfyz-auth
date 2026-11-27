<?php

namespace App\User\Exception;

class RoleNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Role "%s" not found.', $id));
    }
}
