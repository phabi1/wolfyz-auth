<?php

namespace App\Oidc\Server\Entity;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use EntityTrait;
    use TokenEntityTrait;

    /** OIDC nonce carried over from the authorization request, set via NonceContext. */
    private ?string $nonce = null;

    private ?string $issuer = null;

    /** "kid" header: identifies the signing key in the JWKS. */
    private ?string $keyId = null;

    /** @var string[]|null permissions of the user when the token was issued */
    private ?array $permissions = null;

    /** @var array<string, mixed> other claims about the user (email) */
    private array $userClaims = [];

    public function setNonce(?string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setIssuer(?string $issuer): void
    {
        $this->issuer = $issuer;
    }

    public function setKeyId(?string $keyId): void
    {
        $this->keyId = $keyId;
    }

    /**
     * Permissions granted by the user's roles, added to the JWT ("permissions"
     * claim) so that the resource servers (club API) can authorize requests
     * without calling the auth server. Renewed with every token refresh.
     *
     * @param string[] $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = array_values($permissions);
    }

    /** @return string[]|null */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * Claims about the user for the resource servers, e.g. "email" and
     * "email_verified" (the club API finds the user's event registrations).
     *
     * @param array<string, mixed> $claims
     */
    public function setUserClaims(array $claims): void
    {
        $this->userClaims = $claims;
    }

    /**
     * Same claims as league's AccessTokenTrait::convertToJWT() (private, so it
     * cannot be extended), plus "iss", "permissions", the user claims and the
     * "kid" header.
     */
    public function toString(): string
    {
        $this->initJwtConfiguration();
        $now = new DateTimeImmutable();

        $builder = $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
            ->withClaim('scopes', array_map(
                static fn (ScopeEntityInterface $scope) => $scope->getIdentifier(),
                $this->getScopes(),
            ));

        if ($this->keyId !== null && $this->keyId !== '') {
            $builder = $builder->withHeader('kid', $this->keyId);
        }
        if ($this->issuer !== null && $this->issuer !== '') {
            $builder = $builder->issuedBy($this->issuer);
        }
        if ($this->permissions !== null) {
            $builder = $builder->withClaim('permissions', $this->permissions);
        }
        foreach ($this->userClaims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey())->toString();
    }
}
