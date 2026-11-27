<?php

namespace App\User\Api;

use App\Oidc\AccessTokenValidator;
use App\User\Role\RoleService;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Access control of the /api/users, /api/roles and /api/permissions
 * endpoints: a valid access token carrying the "users" scope, issued to a
 * user who has (at least one of) the required permissions.
 */
class UserApiGuard
{
    public const SCOPE = 'users';

    public function __construct(
        private readonly AccessTokenValidator $tokenValidator,
        private readonly RoleService $roles,
    ) {
    }

    /**
     * @param string[] $anyOf the caller needs at least one of these permissions
     * @return string identifier of the authenticated user
     * @throws OAuthServerException invalid_token (401), insufficient_scope or access_denied (403)
     */
    public function authorize(ServerRequestInterface $request, array $anyOf): string
    {
        $request = $this->tokenValidator->validate($request);
        $userId = $request->getAttribute('oauth_user_id');

        $scopes = $request->getAttribute('oauth_scopes', []);
        if (!is_array($scopes) || !in_array(self::SCOPE, $scopes, true)) {
            throw new OAuthServerException('The "users" scope is required.', 0, 'insufficient_scope', 403);
        }

        if (array_intersect($anyOf, $this->roles->userPermissions($userId)) === []) {
            throw new OAuthServerException(
                sprintf('Permission required: %s.', implode(' or ', $anyOf)),
                0,
                'access_denied',
                403,
            );
        }

        return $userId;
    }
}
