<?php

namespace App\Oidc\Server\Entity;

use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface
{
    public function __construct(
        private readonly string $identifier,
        public readonly string $email = '',
        public readonly string $name = '',
        public readonly ?string $givenName = null,
        public readonly ?string $familyName = null,
        public readonly bool $emailVerified = false,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
