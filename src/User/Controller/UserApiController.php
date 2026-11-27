<?php

namespace App\User\Controller;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\User\Role\Permissions;

/**
 * REST API used by the club application to manage users ("users.manage").
 *
 *   GET    /api/users            list (page, size, sort, search)
 *   POST   /api/users            create
 *   GET    /api/users/{id}       read
 *   PUT    /api/users/{id}       partial update (PATCH is an alias)
 *   DELETE /api/users/{id}       delete
 */
class UserApiController extends AbstractUserApiController
{
    private const PERMISSION = [Permissions::USERS_MANAGE];

    public function collectionAction(Request $request): Response
    {
        $permissions = ['GET' => self::PERMISSION, 'POST' => self::PERMISSION];

        return $this->handle($request, $permissions, function (string $actorId) use ($request) {
            $users = $this->getService('user.admin');
            if ($request->method === 'GET') {
                return new JsonResponse($users->list($request->query->all()));
            }

            return new JsonResponse($users->create($request->body->all(), $actorId), 201);
        });
    }

    public function itemAction(Request $request): Response
    {
        $permissions = array_fill_keys(['GET', 'PUT', 'PATCH', 'DELETE'], self::PERMISSION);

        return $this->handle($request, $permissions, function (string $actorId) use ($request) {
            $users = $this->getService('user.admin');
            $id = (string) $request->attributes->get('id');

            if ($request->method === 'DELETE') {
                $users->delete($id, $actorId);

                return $this->noContent();
            }

            return new JsonResponse($request->method === 'GET'
                ? $users->get($id)
                : $users->update($id, $request->body->all(), $actorId));
        });
    }
}
