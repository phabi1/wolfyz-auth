<?php

namespace App\Oidc\Server\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;

    /**
     * @param string[] $grantTypes grant types allowed for this client (auth_oauth_client.grant_types)
     */
    public function __construct(
        string $identifier,
        string $name,
        array|string $redirectUri,
        bool $isConfidential,
        private readonly array $grantTypes = [],
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
        $this->isConfidential = $isConfidential;
    }

    /**
     * Called by league/oauth2-server for every client, public or confidential.
     */
    public function supportsGrantType(string $grantType): bool
    {
        return in_array($grantType, $this->grantTypes, true);
    }
}
