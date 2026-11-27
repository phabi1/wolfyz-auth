<?php

namespace App\User\Controller;

use App\Core\Http\JsonResponse;
use App\Core\Http\Psr7;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Mvc\Controller\AbstractController;
use App\User\Api\UserApiGuard;
use App\User\Exception\RoleNotFoundException;
use App\User\Exception\UserNotFoundException;
use App\User\Exception\UserOperationForbiddenException;
use App\User\Exception\UserValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * Shared behaviour of the user administration API controllers: method check,
 * authorization and mapping of domain errors to HTTP responses.
 */
abstract class AbstractUserApiController extends AbstractController
{
    /**
     * @param array<string, string[]> $permissions HTTP method => permissions (any of) required
     * @param callable(string $actorId): Response $action
     */
    protected function handle(Request $request, array $permissions, callable $action): Response
    {
        if (!isset($permissions[$request->method])) {
            return new JsonResponse(
                ['error' => 'method_not_allowed', 'message' => 'Unsupported HTTP method.'],
                405,
                ['Allow' => implode(', ', array_keys($permissions))],
            );
        }

        try {
            $actorId = $this->getService('user.api.guard')
                ->authorize(Psr7::requestFromGlobals(), $permissions[$request->method]);
        } catch (OAuthServerException $e) {
            $challenge = 'Bearer';
            if ($request->header('Authorization') !== null) {
                $challenge .= ' error="' . $e->getErrorType() . '"';
            }
            if ($e->getErrorType() === 'insufficient_scope') {
                $challenge .= ', scope="' . UserApiGuard::SCOPE . '"';
            }

            return new JsonResponse(
                ['error' => $e->getErrorType(), 'message' => $e->getMessage()],
                $e->getHttpStatusCode(),
                ['WWW-Authenticate' => $challenge],
            );
        }

        try {
            return $action($actorId);
        } catch (UserValidationException $e) {
            return new JsonResponse(
                ['error' => 'validation_failed', 'message' => $e->getMessage(), 'errors' => $e->getErrors()],
                422,
            );
        } catch (UserNotFoundException | RoleNotFoundException $e) {
            return new JsonResponse(['error' => 'not_found', 'message' => $e->getMessage()], 404);
        } catch (UserOperationForbiddenException $e) {
            return new JsonResponse(['error' => 'forbidden', 'message' => $e->getMessage()], 403);
        }
    }

    protected function noContent(): Response
    {
        return new Response('', 204, ['Cache-Control' => 'no-store']);
    }
}
