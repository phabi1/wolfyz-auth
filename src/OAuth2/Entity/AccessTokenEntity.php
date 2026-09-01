<?php

namespace App\OAuth2\Entity;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use EntityTrait;
    use TokenEntityTrait;
    use AccessTokenTrait;

    public function __construct(ClientEntity $client, array $scopes = [], ?string $userIdentifier = null)
    {
        $this->setClient($client);
        
        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }

        if ($userIdentifier !== null) {
            $this->setUserIdentifier($userIdentifier);
        }
    }
}