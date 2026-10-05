<?php

namespace App\Oidc\Server;

use App\Core\Config\Parameters;
use App\Oidc\Server\Repository\AccessTokenRepository;
use App\Oidc\Server\Repository\AuthCodeRepository;
use App\Oidc\Server\Repository\ClientRepository;
use App\Oidc\Server\Repository\RefreshTokenRepository;
use App\Oidc\Server\Repository\ScopeRepository;
use App\Oidc\Server\Repository\UserRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use App\Oidc\Jwks;

class Server
{
    public function __construct(
        private readonly Parameters $parameters,
        public readonly ClientRepository $clientRepository,
        public readonly ScopeRepository $scopeRepository,
        public readonly UserRepository $userRepository,
        public readonly AccessTokenRepository $accessTokenRepository,
        public readonly RefreshTokenRepository $refreshTokenRepository,
        public readonly AuthCodeRepository $authCodeRepository,
        public readonly Jwks $jwks,
    ) {
    }

    public function authorizationServer(): AuthorizationServer
    {
        $privateKey = new CryptKey(
            $this->parameters->get('oidc.private_key'),
            $this->parameters->get('oidc.passphrase'),
            false
        );

        $responseType = new IdTokenResponse(
            $this->userRepository,
            $this->parameters->get('oidc.issuer'),
            $this->parameters->get('oidc.public_key'),
            $this->jwks,
        );

        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $privateKey,
            $this->parameters->get('oidc.encryption_key'),
            $responseType
        );

        $authCodeGrant = new AuthCodeGrant(
            $this->authCodeRepository,
            $this->refreshTokenRepository,
            new \DateInterval($this->parameters->get('oidc.auth_code_ttl'))
        );
        $authCodeGrant->setRefreshTokenTTL(new \DateInterval($this->parameters->get('oidc.refresh_token_ttl')));

        $server->enableGrantType($authCodeGrant, new \DateInterval($this->parameters->get('oidc.access_token_ttl')));

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new \DateInterval($this->parameters->get('oidc.refresh_token_ttl')));
        $server->enableGrantType($refreshTokenGrant, new \DateInterval($this->parameters->get('oidc.access_token_ttl')));

        return $server;
    }

    public function resourceServer(): ResourceServer
    {
        $publicKey = new CryptKey($this->parameters->get('oidc.public_key'), null, false);

        return new ResourceServer($this->accessTokenRepository, $publicKey);
    }
}
