<?php

namespace App\User\Role;

use App\Core\Db\Db;
use App\Core\Entity\EntityManager;
use App\User\Exception\RoleNotFoundException;
use App\User\Exception\UserOperationForbiddenException;
use App\User\Exception\UserValidationException;

/**
 * Roles, their permissions and the roles of each user.
 *
 * Privilege escalation rule: an actor may only create, edit, delete, grant or
 * revoke roles whose permissions are all among the actor's own permissions.
 */
class RoleService
{
    public const ADMIN = 'admin';
    private const ID_PATTERN = '/^[a-z0-9][a-z0-9_-]{0,79}$/D';

    private readonly Db $db;

    public function __construct(EntityManager $entityManager)
    {
        $this->db = $entityManager->getDb();
    }

    /**
     * Every role with its permissions and number of users, system roles first.
     */
    public function all(): array
    {
        $rows = $this->db->rows(
            'SELECT r.*, (SELECT COUNT(*) FROM auth_user_role ur WHERE ur.role_id = r.id) AS user_count'
            . ' FROM auth_role r ORDER BY r.is_system DESC, r.label ASC'
        );
        $permissions = $this->permissionsByRole();

        return array_map(fn ($row) => $this->serialize($row, $permissions), $rows);
    }

    public function get(string $id): array
    {
        $row = $this->db->row(
            'SELECT r.*, (SELECT COUNT(*) FROM auth_user_role ur WHERE ur.role_id = r.id) AS user_count'
            . ' FROM auth_role r WHERE r.id = ' . $this->db->escape($id)
        );
        if (!$row) {
            throw new RoleNotFoundException($id);
        }

        return $this->serialize($row, $this->permissionsByRole([$id]));
    }

    /** @return string[] identifiers of the existing roles among $ids */
    public function existing(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $rows = $this->db->rows(
            'SELECT id FROM auth_role WHERE ' . $this->db->expr()->in('id', array_values($ids))->build()
        );

        return array_map(fn ($row) => $row->id, $rows);
    }

    /** @return string[] */
    public function userRoleIds(string $userId): array
    {
        return $this->rolesForUsers([$userId])[$userId] ?? [];
    }

    /**
     * @param string[] $userIds
     * @param bool $withLabels true: [userId => [['id' => …, 'label' => …]]], false: [userId => [roleId, …]]
     */
    public function rolesForUsers(array $userIds, bool $withLabels = false): array
    {
        if ($userIds === []) {
            return [];
        }
        $rows = $this->db->rows(
            'SELECT ur.user_id, r.id, r.label FROM auth_user_role ur'
            . ' INNER JOIN auth_role r ON r.id = ur.role_id'
            . ' WHERE ' . $this->db->expr()->in('ur.user_id', array_values($userIds))->build()
            . ' ORDER BY r.is_system DESC, r.label ASC'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row->user_id][] = $withLabels ? ['id' => $row->id, 'label' => $row->label] : $row->id;
        }

        return $result;
    }

    /**
     * Permissions granted by the given roles. The admin role grants everything.
     *
     * @return string[]
     */
    public function permissionsOfRoles(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }
        if (in_array(self::ADMIN, $roleIds, true)) {
            return Permissions::ids();
        }
        $rows = $this->db->rows(
            'SELECT DISTINCT permission FROM auth_role_permission WHERE '
            . $this->db->expr()->in('role_id', array_values($roleIds))->build()
        );

        // Ignore permissions removed from the catalogue.
        return array_values(array_intersect(Permissions::ids(), array_map(fn ($row) => $row->permission, $rows)));
    }

    /** @return string[] */
    public function userPermissions(string $userId): array
    {
        return $this->permissionsOfRoles($this->userRoleIds($userId));
    }

    /**
     * Replaces the roles of a user. Checks are made by the caller.
     */
    public function setUserRoles(string $userId, array $roleIds): void
    {
        $this->db->delete('auth_user_role', $this->db->expr()->eq('user_id', $userId));
        foreach (array_unique($roleIds) as $roleId) {
            $this->db->insert('auth_user_role', ['user_id' => $userId, 'role_id' => $roleId]);
        }
    }

    /**
     * @param string[] $actorPermissions
     */
    public function create(array $data, array $actorPermissions): array
    {
        $values = $this->validate($data, null);
        $this->assertWithin($values['permissions'], $actorPermissions);

        if (isset($data['id']) && $data['id'] !== '' && $data['id'] !== null) {
            $id = is_string($data['id']) ? $data['id'] : '';
            if (!preg_match(self::ID_PATTERN, $id)) {
                throw new UserValidationException(['id' => 'Use lowercase letters, digits, "-" or "_" (80 characters max).']);
            }
            if ($this->existing([$id]) !== []) {
                throw new UserValidationException(['id' => 'A role with this identifier already exists.']);
            }
        } else {
            $id = $this->uniqueId($this->slug($values['label']));
        }

        $this->transaction(function () use ($id, $values) {
            $this->db->insert('auth_role', [
                'id' => $id,
                'label' => $values['label'],
                'description' => $values['description'],
                'is_system' => 0,
            ]);
            $this->savePermissions($id, $values['permissions']);
        });

        return $this->get($id);
    }

    /**
     * Partial update of a non-system role.
     *
     * @param string[] $actorPermissions
     */
    public function update(string $id, array $data, array $actorPermissions): array
    {
        $role = $this->get($id);
        $this->assertEditable($role, $actorPermissions);
        $values = $this->validate($data, $role);
        $this->assertWithin($values['permissions'], $actorPermissions);

        $this->transaction(function () use ($id, $values) {
            $this->db->update('auth_role', [
                'label' => $values['label'],
                'description' => $values['description'],
            ], $this->db->expr()->eq('id', $id));
            $this->savePermissions($id, $values['permissions']);
        });

        return $this->get($id);
    }

    /**
     * Deletes a non-system role; its users simply lose it.
     *
     * @param string[] $actorPermissions
     */
    public function delete(string $id, array $actorPermissions): void
    {
        $role = $this->get($id);
        $this->assertEditable($role, $actorPermissions);

        $this->transaction(function () use ($id) {
            // Explicit deletes: SQLite (tests) does not enforce the foreign keys.
            $this->db->delete('auth_user_role', $this->db->expr()->eq('role_id', $id));
            $this->db->delete('auth_role_permission', $this->db->expr()->eq('role_id', $id));
            $this->db->delete('auth_role', $this->db->expr()->eq('id', $id));
        });
    }

    /**
     * @param string[] $permissions
     * @param string[] $actorPermissions
     */
    public function assertWithin(array $permissions, array $actorPermissions): void
    {
        $missing = array_values(array_diff($permissions, $actorPermissions));
        if ($missing !== []) {
            throw new UserOperationForbiddenException(sprintf(
                'You cannot grant permissions that you do not have yourself (%s).',
                implode(', ', $missing),
            ));
        }
    }

    private function assertEditable(array $role, array $actorPermissions): void
    {
        if ($role['is_system']) {
            throw new UserOperationForbiddenException('System roles cannot be modified or deleted.');
        }
        $this->assertWithin($role['permissions'], $actorPermissions);
    }

    /**
     * @return array{label: string, description: ?string, permissions: string[]}
     */
    private function validate(array $data, ?array $current): array
    {
        $errors = [];
        $values = [
            'label' => $current['label'] ?? '',
            'description' => $current['description'] ?? null,
            'permissions' => $current['permissions'] ?? [],
        ];

        if (array_key_exists('label', $data) || $current === null) {
            $label = is_string($data['label'] ?? null) ? trim($data['label']) : '';
            if ($label === '') {
                $errors['label'] = 'This field is required.';
            } elseif (mb_strlen($label) > 190) {
                $errors['label'] = 'This value is too long (190 characters max).';
            } else {
                $values['label'] = $label;
            }
        }

        if (array_key_exists('description', $data)) {
            $description = $data['description'];
            if ($description !== null && !is_string($description)) {
                $errors['description'] = 'This value must be a string.';
            } elseif ($description !== null && mb_strlen(trim($description)) > 255) {
                $errors['description'] = 'This value is too long (255 characters max).';
            } else {
                $description = $description === null ? null : trim($description);
                $values['description'] = $description === '' ? null : $description;
            }
        }

        if (array_key_exists('permissions', $data)) {
            $permissions = $data['permissions'];
            if (!is_array($permissions) || array_filter($permissions, fn ($p) => !is_string($p)) !== []) {
                $errors['permissions'] = 'This value must be a list of permissions.';
            } else {
                $unknown = array_values(array_filter($permissions, fn ($p) => !Permissions::exists($p)));
                if ($unknown !== []) {
                    $errors['permissions'] = sprintf('Unknown permissions: %s.', implode(', ', $unknown));
                } else {
                    // Catalogue order, without duplicates.
                    $values['permissions'] = array_values(array_intersect(Permissions::ids(), $permissions));
                }
            }
        }

        if ($errors !== []) {
            throw new UserValidationException($errors);
        }

        return $values;
    }

    private function savePermissions(string $roleId, array $permissions): void
    {
        $this->db->delete('auth_role_permission', $this->db->expr()->eq('role_id', $roleId));
        foreach ($permissions as $permission) {
            $this->db->insert('auth_role_permission', ['role_id' => $roleId, 'permission' => $permission]);
        }
    }

    /** @return array<string, string[]> role id => permissions */
    private function permissionsByRole(?array $roleIds = null): array
    {
        $sql = 'SELECT role_id, permission FROM auth_role_permission';
        if ($roleIds !== null) {
            $sql .= ' WHERE ' . $this->db->expr()->in('role_id', $roleIds)->build();
        }
        $result = [];
        foreach ($this->db->rows($sql) as $row) {
            $result[$row->role_id][] = $row->permission;
        }

        return $result;
    }

    private function serialize(\stdClass $row, array $permissionsByRole): array
    {
        $system = (bool) $row->is_system;
        $permissions = $row->id === self::ADMIN
            ? Permissions::ids()
            : array_values(array_intersect(Permissions::ids(), $permissionsByRole[$row->id] ?? []));

        return [
            'id' => $row->id,
            'label' => $row->label,
            'description' => $row->description,
            'is_system' => $system,
            'permissions' => $permissions,
            'user_count' => (int) ($row->user_count ?? 0),
            'created_at' => isset($row->created_at) ? strtotime($row->created_at) : null,
        ];
    }

    private function slug(string $label): string
    {
        $ascii = function_exists('iconv') ? (@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label) : $label;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');

        return substr($slug, 0, 70) ?: 'role';
    }

    private function uniqueId(string $base): string
    {
        $id = $base;
        for ($i = 2; $this->existing([$id]) !== []; $i++) {
            $id = $base . '-' . $i;
        }

        return $id;
    }

    private function transaction(callable $callback): void
    {
        $this->db->beginTransaction();
        try {
            $callback();
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
