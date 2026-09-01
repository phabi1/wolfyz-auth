<?php

namespace App\OAuth2\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    protected string $secret;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if ($key === 'identifier') {
                $this->setIdentifier($value);
            } else {
                $this->$key = $value;
            }
        }
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}