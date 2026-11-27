<?php

namespace App\Oidc;

use App\Oidc\Server\Entity\UserEntity;
use App\Oidc\Server\Repository\UserRepository;
use App\Oidc\Server\Server;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;

class Userinfo
{
    public function __construct(
        private readonly Server $server,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function build(ServerRequestInterface $request): array
    {
        $request = (new AccessTokenValidator($this->server))->validate($request);
        $identifier = $request->getAttribute('oauth_user_id');

        $scopes = $request->getAttribute('oauth_scopes', []);
        if (!is_array($scopes) || !in_array('openid', $scopes, true)) {
            throw new OAuthServerException('The openid scope is required.', 0, 'insufficient_scope', 403);
        }

        $user = $this->userRepository->getUserEntityByIdentifier($identifier);
        if ($user === null) {
            throw new OAuthServerException('The user associated with the access token no longer exists.', 0, 'invalid_token', 401);
        }
        if (!$user instanceof UserEntity) {
            throw new \RuntimeException('Invalid OIDC user entity.');
        }

        $claims = ['sub' => $user->getIdentifier()];
        if (in_array('profile', $scopes, true)) {
            $claims['name'] = $user->name;
            if ($user->givenName !== null) {
                $claims['given_name'] = $user->givenName;
            }
            if ($user->familyName !== null) {
                $claims['family_name'] = $user->familyName;
            }
        }
        if (in_array('email', $scopes, true)) {
            $claims['email'] = $user->email;
            $claims['email_verified'] = $user->emailVerified;
        }
        if (in_array('roles', $scopes, true)) {
            $claims['roles'] = $user->roles;
            $claims['permissions'] = $user->permissions;
        }

        return $claims;
    }
}