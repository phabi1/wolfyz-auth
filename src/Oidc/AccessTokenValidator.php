<?php

namespace App\Oidc;

use App\Oidc\Server\Server;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validates the Bearer access token of a request (signature, validity period,
 * revocation) and rejects ID tokens or tokens with incomplete claims.
 *
 * The returned request carries the "oauth_user_id" and "oauth_scopes"
 * attributes set by the league resource server.
 */
class AccessTokenValidator
{
    public function __construct(private readonly Server $server)
    {
    }

    /**
     * @throws OAuthServerException invalid_token (401)
     */
    public function validate(ServerRequestInterface $request): ServerRequestInterface
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

        return $request;
    }
}
