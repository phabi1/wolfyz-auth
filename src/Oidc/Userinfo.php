<?php

namespace App\Oidc;

use App\Oidc\Server\Entity\UserEntity;
use App\Oidc\Server\Repository\UserRepository;
use App\Oidc\Server\Server;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
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
        $headers = $request->getHeader('Authorization');
        if (count($headers) !== 1 || !preg_match('/^Bearer +([A-Za-z0-9\-._~+\/]+=*)$/i', $headers[0], $matches)) {
            throw new OAuthServerException('A Bearer access token is required.', 0, 'invalid_token', 401);
        }

        try {
            // Reject ID tokens and incomplete claims before the resource server uses them.
            $token = (new Parser(new JoseEncoder()))->parse($matches[1]);
            if (!$token instanceof UnencryptedToken) {
                throw new OAuthServerException('An access token is required.', 0, 'invalid_token', 401);
            }
            $claims = $token->claims();
            $audience = $claims->get('aud');
            if (!is_string($claims->get('jti')) || $claims->get('jti') === ''
                || !$claims->get('exp') instanceof \DateTimeImmutable
                || !is_array($audience) || !isset($audience[0]) || !is_string($audience[0])
                || !is_array($claims->get('scopes'))
            ) {
                throw new OAuthServerException('An access token is required.', 0, 'invalid_token', 401);
            }
            $request = $this->server->resourceServer()->validateAuthenticatedRequest($request);
        } catch (OAuthServerException | JwtException $e) {
            throw new OAuthServerException('The access token is invalid or expired.', 0, 'invalid_token', 401, null, null, $e);
        }

        $identifier = $request->getAttribute('oauth_user_id');
        if (!is_string($identifier) || $identifier === '') {
            throw new OAuthServerException('The access token has no user subject.', 0, 'invalid_token', 401);
        }

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

        return $claims;
    }
}