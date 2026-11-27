<?php

namespace App\Oidc;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

/**
 * Validates the post_logout_redirect_uri of the end session endpoint
 * (OpenID Connect RP-Initiated Logout) to prevent open redirects.
 *
 * The URI is accepted only when it is one of the registered redirect URIs of the
 * client, identified by the client_id parameter or the audience of id_token_hint.
 * The ID token signature is not checked: a forged hint can only name an existing
 * client, whose registered URIs are trusted anyway.
 */
class PostLogoutRedirect
{
    public function __construct(private readonly ClientRepositoryInterface $clientRepository)
    {
    }

    /**
     * Returns the URI to redirect to (with the state appended), or null when it is
     * missing or not allowed.
     */
    public function resolve(?string $redirectUri, ?string $clientId = null, ?string $idTokenHint = null, ?string $state = null): ?string
    {
        if ($redirectUri === null || $redirectUri === '') {
            return null;
        }

        $audiences = $idTokenHint ? $this->audiences($idTokenHint) : [];

        if ($clientId !== null && $clientId !== '') {
            // The hint, when present, must have been issued to this client.
            if ($idTokenHint && !in_array($clientId, $audiences, true)) {
                return null;
            }
            $clientIds = [$clientId];
        } else {
            $clientIds = $audiences;
        }

        foreach ($clientIds as $id) {
            $client = $this->clientRepository->getClientEntity($id);
            if ($client && in_array($redirectUri, (array) $client->getRedirectUri(), true)) {
                return $this->withState($redirectUri, $state);
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function audiences(string $idToken): array
    {
        try {
            $token = (new Parser(new JoseEncoder()))->parse($idToken);
        } catch (JwtException) {
            return [];
        }
        if (!$token instanceof UnencryptedToken) {
            return [];
        }

        return array_values(array_filter(
            (array) $token->claims()->get('aud', []),
            static fn ($aud) => is_string($aud) && $aud !== '',
        ));
    }

    private function withState(string $uri, ?string $state): string
    {
        if ($state === null || $state === '') {
            return $uri;
        }

        $fragment = '';
        if (($pos = strpos($uri, '#')) !== false) {
            $fragment = substr($uri, $pos);
            $uri = substr($uri, 0, $pos);
        }

        return $uri . (str_contains($uri, '?') ? '&' : '?') . 'state=' . rawurlencode($state) . $fragment;
    }
}
