<?php

namespace App\User\Admin;

use App\Core\Db\Db;
use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Entity\Exception\DuplicateEntityException;
use App\User\Exception\UserNotFoundException;
use App\User\Exception\UserOperationForbiddenException;
use App\User\Exception\UserValidationException;
use App\User\Repository\UserRepository;
use App\User\Role\Permissions;
use App\User\Role\RoleService;

/**
 * User management used by the /api/users endpoints.
 *
 * Every method returns users already serialized for the API: the password
 * hash is never exposed.
 *
 * $actorId is the user performing the operation (null: system, e.g. CLI).
 * To prevent privilege escalation, an actor may only modify or delete users
 * whose permissions are all among the actor's own permissions, may only grant
 * or revoke roles within those permissions, and cannot change their own roles.
 */
class UserAdminService
{
    public const DEFAULT_PAGE_SIZE = 10;
    public const MAX_PAGE_SIZE = 100;
    private const PASSWORD_MIN_LENGTH = 8;
    // bcrypt ignores everything after 72 bytes.
    private const PASSWORD_MAX_LENGTH = 72;
    private const SORTABLE_FIELDS = ['email', 'name', 'firstname', 'lastname', 'created_at', 'email_verified'];

    private readonly UserRepository $users;
    private readonly EntityRepositoryInterface $userProviders;
    private readonly Db $db;

    public function __construct(EntityManager $entityManager, private readonly RoleService $roles)
    {
        $users = $entityManager->getRepository('user');
        if (!$users instanceof UserRepository) {
            throw new \RuntimeException('The "user" repository must be an instance of ' . UserRepository::class . '.');
        }
        $this->users = $users;
        $this->userProviders = $entityManager->getRepository('auth-user-provider');
        $this->db = $entityManager->getDb();
    }

    /**
     * @param array{page?: mixed, size?: mixed, sort?: mixed, search?: mixed} $query
     * @return array{items: array, total: int, page: int, size: int}
     */
    public function list(array $query = []): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $size = min(self::MAX_PAGE_SIZE, max(1, (int) ($query['size'] ?? self::DEFAULT_PAGE_SIZE)));
        $search = is_string($query['search'] ?? null) ? $query['search'] : '';
        $sort = $this->parseSort($query['sort'] ?? null);

        $items = $this->users->search($search, $sort, $size, ($page - 1) * $size);
        $roles = $this->roles->rolesForUsers(array_map(fn ($user) => $user->id, $items), true);

        return [
            'items' => array_map(fn ($user) => $this->serialize($user, $roles[$user->id] ?? []), $items),
            'total' => $this->users->countSearch($search),
            'page' => $page,
            'size' => $size,
        ];
    }

    public function get(string $id): array
    {
        return $this->serializeWithRoles($this->load($id));
    }

    public function create(array $data, ?string $actorId = null): array
    {
        $values = $this->validate($data, null);
        $actorPermissions = $this->actorPermissions($actorId);

        $roleIds = $values['roles'] ?? [];
        unset($values['roles']);
        $this->roles->assertWithin($this->roles->permissionsOfRoles($roleIds), $actorPermissions);

        $password = $values['password'] ?? null;
        unset($values['password']);

        try {
            $this->users->beginTransaction();
            // Without a password the account can only be activated through
            // "forgot password": the random value is never disclosed.
            // The repository hashes password_hash before persisting it.
            $user = $this->users->insert(array_merge($values, [
                'id' => bin2hex(random_bytes(16)),
                'password_hash' => $password ?? bin2hex(random_bytes(32)),
                'email_verified' => $values['email_verified'] ?? false,
            ]));
            $this->userProviders->insert([
                'user_id' => $user->id,
                'auth_provider_id' => 'local',
                'value' => $user->email,
            ]);
            $this->roles->setUserRoles($user->id, $roleIds);
            $this->users->commit();
        } catch (DuplicateEntityException) {
            $this->users->rollback();
            throw new UserValidationException(['email' => 'An account with this email already exists.']);
        } catch (\Throwable $e) {
            $this->users->rollback();
            throw $e;
        }

        return $this->serializeWithRoles($user);
    }

    /**
     * Partial update: only the provided fields are changed.
     */
    public function update(string $id, array $data, ?string $actorId = null): array
    {
        $current = $this->load($id);
        $actorPermissions = $this->actorPermissions($actorId);
        $this->assertCanManage($id, $actorPermissions);
        $values = $this->validate($data, $current);

        $roleIds = null;
        if (array_key_exists('roles', $values)) {
            $currentRoles = $this->roles->userRoleIds($id);
            $changed = array_merge(array_diff($values['roles'], $currentRoles), array_diff($currentRoles, $values['roles']));
            if ($changed !== []) {
                if ($actorId !== null && $actorId === $id) {
                    throw new UserOperationForbiddenException('You cannot change your own roles.');
                }
                $this->roles->assertWithin($this->roles->permissionsOfRoles($changed), $actorPermissions);
                $roleIds = $values['roles'];
            }
            unset($values['roles']);
        }

        if (array_key_exists('password', $values)) {
            $values['password_hash'] = $values['password'];
            unset($values['password']);
        }
        if ($values === [] && $roleIds === null) {
            return $this->serializeWithRoles($current);
        }

        try {
            $this->users->beginTransaction();
            $user = $values === [] ? $current : $this->users->update($id, $values);
            if ($roleIds !== null) {
                $this->roles->setUserRoles($id, $roleIds);
            }
            if (isset($values['email']) && $values['email'] !== $current->email) {
                $this->db->update(
                    'auth_user_provider',
                    ['value' => $values['email']],
                    $this->db->expr()->and([
                        $this->db->expr()->eq('user_id', $id),
                        $this->db->expr()->eq('auth_provider_id', 'local'),
                    ]),
                );
            }
            $this->users->commit();
        } catch (DuplicateEntityException | \App\Core\Db\Exception\DuplicateEntryException) {
            $this->users->rollback();
            throw new UserValidationException(['email' => 'An account with this email already exists.']);
        } catch (\Throwable $e) {
            $this->users->rollback();
            throw $e;
        }

        return $this->serializeWithRoles($user);
    }

    /**
     * Deletes the user and revokes all of their OAuth tokens and codes.
     */
    public function delete(string $id, ?string $actorId = null): void
    {
        $this->load($id);
        if ($actorId !== null && $actorId === $id) {
            throw new UserOperationForbiddenException('You cannot delete your own account.');
        }
        $this->assertCanManage($id, $this->actorPermissions($actorId));

        $userId = $this->db->escape($id);
        try {
            $this->users->beginTransaction();
            $this->db->update(
                'auth_oauth_refresh_token',
                ['revoked' => 1],
                'access_token_id IN (SELECT id FROM auth_oauth_access_token WHERE user_id = ' . $userId . ')',
            );
            $this->db->update('auth_oauth_access_token', ['revoked' => 1], 'user_id = ' . $userId);
            $this->db->update('auth_oauth_auth_code', ['revoked' => 1], 'user_id = ' . $userId);
            // auth_user_token and auth_user_provider rows are removed by ON DELETE CASCADE.
            $this->roles->setUserRoles($id, []);
            $this->users->delete($id);
            $this->users->commit();
        } catch (\Throwable $e) {
            $this->users->rollback();
            throw $e;
        }
    }

    /** @return string[] */
    private function actorPermissions(?string $actorId): array
    {
        return $actorId === null ? Permissions::ids() : $this->roles->userPermissions($actorId);
    }

    private function assertCanManage(string $userId, array $actorPermissions): void
    {
        $missing = array_diff($this->roles->userPermissions($userId), $actorPermissions);
        if ($missing !== []) {
            throw new UserOperationForbiddenException('You cannot manage a user who has more permissions than you.');
        }
    }

    private function load(string $id): \stdClass
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            throw new UserNotFoundException($id);
        }

        return $user;
    }

    /**
     * Validates and normalizes the writable fields. $current is null on creation.
     */
    private function validate(array $data, ?\stdClass $current): array
    {
        $creating = $current === null;
        $values = [];
        $errors = [];

        foreach (['firstname', 'lastname', 'name'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = is_string($data[$field]) ? trim($data[$field]) : null;
            if ($value === null || $value === '') {
                $errors[$field] = 'This field is required.';
            } elseif (mb_strlen($value) > 190) {
                $errors[$field] = 'This value is too long (190 characters max).';
            } else {
                $values[$field] = $value;
            }
        }
        if ($creating) {
            foreach (['firstname', 'lastname'] as $field) {
                if (!array_key_exists($field, $data)) {
                    $errors[$field] = 'This field is required.';
                }
            }
        }

        if (array_key_exists('email', $data) || $creating) {
            $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
            if ($email === '') {
                $errors['email'] = 'This field is required.';
            } elseif (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Invalid email address.';
            } else {
                $existing = $this->users->findByEmail($email);
                if ($existing && ($creating || $existing->id !== $current->id)) {
                    $errors['email'] = 'An account with this email already exists.';
                } else {
                    $values['email'] = $email;
                }
            }
        }

        if (array_key_exists('password', $data) && !($creating && $data['password'] === null)) {
            $password = $data['password'];
            if (!is_string($password)
                || strlen($password) < self::PASSWORD_MIN_LENGTH
                || strlen($password) > self::PASSWORD_MAX_LENGTH
            ) {
                $errors['password'] = 'Password must contain 8 to 72 bytes.';
            } else {
                $values['password'] = $password;
            }
        }

        if (array_key_exists('roles', $data)) {
            $roles = $data['roles'];
            if (!is_array($roles) || array_filter($roles, fn ($role) => !is_string($role)) !== []) {
                $errors['roles'] = 'This value must be a list of role identifiers.';
            } else {
                $roles = array_values(array_unique($roles));
                $unknown = array_diff($roles, $this->roles->existing($roles));
                if ($unknown !== []) {
                    $errors['roles'] = sprintf('Unknown roles: %s.', implode(', ', $unknown));
                } else {
                    $values['roles'] = $roles;
                }
            }
        }

        foreach (['email_verified'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = is_bool($data[$field]) ? $data[$field]
                : filter_var($data[$field], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($value === null) {
                $errors[$field] = 'This value must be a boolean.';
            } else {
                $values[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new UserValidationException($errors);
        }

        // Keep the display name in sync with firstname/lastname unless it is set explicitly.
        if (!isset($values['name']) && (isset($values['firstname']) || isset($values['lastname']))) {
            $values['name'] = trim(
                ($values['firstname'] ?? $current->firstname ?? '') . ' ' . ($values['lastname'] ?? $current->lastname ?? '')
            );
        }

        return $values;
    }

    /**
     * "lastname|asc,firstname" => [['lastname', 'asc'], ['firstname', 'asc']]
     */
    private function parseSort(mixed $sort): array
    {
        if (!is_string($sort) || trim($sort) === '') {
            return [['created_at', 'desc']];
        }
        $result = [];
        foreach (explode(',', $sort) as $part) {
            [$field, $order] = array_pad(array_map('trim', explode('|', $part, 2)), 2, 'asc');
            if (!in_array($field, self::SORTABLE_FIELDS, true)) {
                throw new UserValidationException(['sort' => sprintf('Unsupported sort field "%s".', $field)]);
            }
            $result[] = [$field, strtolower($order) === 'desc' ? 'desc' : 'asc'];
        }

        return $result;
    }

    private function serializeWithRoles(\stdClass $user): array
    {
        return $this->serialize($user, $this->roles->rolesForUsers([$user->id], true)[$user->id] ?? []);
    }

    /**
     * @param array<array{id: string, label: string}> $roles
     */
    private function serialize(\stdClass $user, array $roles): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'email_verified' => (bool) $user->email_verified,
            'name' => $user->name,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'roles' => $roles,
            'created_at' => $user->created_at,
        ];
    }
}
