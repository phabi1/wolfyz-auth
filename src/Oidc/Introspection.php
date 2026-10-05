<?php

namespace App\Oidc;

class Introspection
{
    private $issuer;

    public function __construct($issuer) {
        $this->issuer = $issuer;
    }

    public function build() {
        return [
            'issuer' => $this->issuer,
            'introspect_endpoint' => $this->issuer . '/.well-known/openid-configuration',
            'jwks_uri' => $this->issuer . '/.well-known/jwks.json',
            'authorization_endpoint' => $this->issuer . '/oidc/authorize',
            'token_endpoint' => $this->issuer . '/oidc/token',
            'userinfo_endpoint' => $this->issuer . '/oidc/userinfo',
            'grant_types_supported' => $this->getGrantTypesSupported(),
        ];
    }

    private function getGrantTypesSupported() {
        return [
            'authorization_code',
            'refresh_token',
            'client_credentials',
            'password',
        ];
    }
}