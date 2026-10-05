<?php

namespace App\Oidc;

/**
 * Builds a JWKS entry (RFC 7517) from the RSA public key used to sign tokens.
 */
class Jwks
{
    public function keyId(string $publicKeyPath): string
    {
        return substr(hash('sha256', file_get_contents($publicKeyPath)), 0, 16);
    }

    public function toJwk(string $publicKeyPath): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_public(file_get_contents($publicKeyPath)));

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $this->keyId($publicKeyPath),
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
