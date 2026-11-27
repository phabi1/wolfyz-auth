<?php

namespace App\User\Controller;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\User\Role\Permissions;

/**
 * REST API used by the club application to manage roles.
 *
 *   GET    /api/permissions      catalogue of permissions
 *   GET    /api/roles            every role (roles.manage or users.manage)
 *   POST   /api/roles            create (roles.manage)
 *   GET    /api/roles/{id}       read (roles.manage or users.manage)
 *   PUT    /api/roles/{id}       partial update (roles.manage; PATCH is an alias)
 *   DELETE /api/roles/{id}       delete (roles.manage)
 */
class RoleApiController extends AbstractUserApiController
{
    private const READ = [Permissions::ROLES_MANAGE, Permissions::USERS_MANAGE];
    private const WRITE = [Permissions::ROLES_MANAGE];

    public function permissionsAction(Request $request): Response
    {
        return $this->handle($request, ['GET' => self::READ], function () {
            $items = [];
            foreach (Permissions::ALL as $id => $description) {
                $items[] = ['id' => $id, 'description' => $description];
            }

            return new JsonResponse(['items' => $items]);
        });
    }

    public function collectionAction(Request $request): Response
    {
        $permissions = ['GET' => self::READ, 'POST' => self::WRITE];

        return $this->handle($request, $permissions, function (string $actorId) use ($request) {
            $roles = $this->getService('user.roles');
            if ($request->method === 'GET') {
                $items = $roles->all();

                return new JsonResponse(['items' => $items, 'total' => count($items)]);
            }

            return new JsonResponse(
                $roles->create($request->body->all(), $roles->userPermissions($actorId)),
                201,
            );
        });
    }

    public function itemAction(Request $request): Response
    {
        $permissions = ['GET' => self::READ, 'PUT' => self::WRITE, 'PATCH' => self::WRITE, 'DELETE' => self::WRITE];

        return $this->handle($request, $permissions, function (string $actorId) use ($request) {
            $roles = $this->getService('user.roles');
            $id = (string) $request->attributes->get('id');

            return match ($request->method) {
                'GET' => new JsonResponse($roles->get($id)),
                'DELETE' => (function () use ($roles, $id, $actorId) {
                    $roles->delete($id, $roles->userPermissions($actorId));

                    return $this->noContent();
                })(),
                default => new JsonResponse(
                    $roles->update($id, $request->body->all(), $roles->userPermissions($actorId)),
                ),
            };
        });
    }
}
