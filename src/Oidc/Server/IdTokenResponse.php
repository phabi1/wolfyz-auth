<?php

namespace App\Oidc\Server;

use App\Oidc\Jwks;
use App\Oidc\Server\Entity\AccessTokenEntity;
use App\Oidc\Server\Repository\UserRepository;
use Firebase\JWT\JWT;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

/**
 * Extends the standard OAuth2 bearer token response with an OIDC "id_token"
 * whenever the "openid" scope was granted.
 */
class IdTokenResponse extends BearerTokenResponse
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly string $issuer,
        private readonly string $publicKeyPath,
        private readonly Jwks $jwks,
    ) {
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        $scopes = array_map(fn($scope) => $scope->getIdentifier(), $accessToken->getScopes());

        if (!in_array('openid', $scopes, true) || $accessToken->getUserIdentifier() === null) {
            return [];
        }

        $user = $this->userRepository->getUserEntityByIdentifier($accessToken->getUserIdentifier());
        $now = time();

        $claims = [
            'iss' => $this->issuer,
            'sub' => $accessToken->getUserIdentifier(),
            'aud' => $accessToken->getClient()->getIdentifier(),
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        if ($accessToken instanceof AccessTokenEntity && $accessToken->getNonce()) {
            $claims['nonce'] = $accessToken->getNonce();
        }

        if ($user) {
            if (in_array('profile', $scopes, true)) {
                $claims['name'] = $user->name;
                $claims['given_name'] = $user->givenName;
                $claims['family_name'] = $user->familyName;
            }
            if (in_array('email', $scopes, true)) {
                $claims['email'] = $user->email;
                $claims['email_verified'] = $user->emailVerified;
            }
        }

        $privateKeyResource = openssl_pkey_get_private(
            file_get_contents($this->privateKey->getKeyPath()),
            $this->privateKey->getPassPhrase() ?? ''
        );

        $idToken = JWT::encode($claims, $privateKeyResource, 'RS256', $this->jwks->keyId($this->publicKeyPath));

        return ['id_token' => $idToken];
    }
}
