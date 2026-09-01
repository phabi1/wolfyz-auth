<?php

namespace App\OAuth2\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

class ScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;
    use ScopeTrait;

    public function __construct(string $identifier)
    {
        $this->setIdentifier($identifier);
    }

    /**
     * Permet à l'entité d'être sérialisée en chaîne de caractères (requis lors de la génération du JWT).
     */
    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }
}