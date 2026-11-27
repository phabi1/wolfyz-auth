<?php

namespace App\Tests\User;

require_once __DIR__ . '/UserTestCase.php';

use App\User\Admin\UserAdminService;
use App\User\Exception\UserNotFoundException;
use App\User\Exception\UserOperationForbiddenException;
use App\User\Exception\UserValidationException;
use App\User\Password\PasswordEncoder;
use App\User\Role\RoleService;

class UserAdminServiceTest extends UserTestCase
{
    private UserAdminService $service;
    private RoleService $roles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get('user.admin');
        $this->roles = $this->container->get('user.roles');
    }

    private function create(array $overrides = []): array
    {
        return $this->service->create(array_merge([
            'email' => ' jane@example.com ',
            'password' => 'secret-password',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ], $overrides));
    }

    public function testCreatesUserWithHashedPasswordAndLocalProvider(): void
    {
        $user = $this->create(['roles' => ['admin'], 'email_verified' => 'true']);

        self::assertSame(32, strlen($user['id']));
        self::assertSame('jane@example.com', $user['email']);
        self::assertSame('Jane Doe', $user['name']);
        self::assertSame([['id' => 'admin', 'label' => 'Administrator']], $user['roles']);
        self::assertTrue($user['email_verified']);
        self::assertIsInt($user['created_at']);
        self::assertArrayNotHasKey('password_hash', $user);

        $hash = $this->db->value('SELECT password_hash FROM auth_user');
        self::assertTrue((new PasswordEncoder())->verify('secret-password', $hash));
        self::assertSame('jane@example.com', $this->db->value("SELECT value FROM auth_user_provider WHERE auth_provider_id = 'local'"));
    }

    public function testCreatesUserWithoutPasswordAsUnusableRandomPassword(): void
    {
        $user = $this->create(['password' => null]);

        self::assertSame([], $user['roles']);
        self::assertFalse($user['email_verified']);
        self::assertNotEmpty($this->db->value('SELECT password_hash FROM auth_user'));
    }

    public function testCreateReportsEveryInvalidField(): void
    {
        try {
            $this->service->create(['email' => 'nope', 'password' => 'short', 'firstname' => ' ', 'roles' => ['ghost'], 'email_verified' => 'maybe']);
            self::fail('Expected a validation error.');
        } catch (UserValidationException $e) {
            self::assertSame(['firstname', 'lastname', 'email', 'password', 'roles', 'email_verified'], array_keys($e->getErrors()));
        }
        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM auth_user'));
    }

    public function testRejectsDuplicateEmail(): void
    {
        $this->create();
        $this->expectException(UserValidationException::class);
        $this->create(['email' => 'jane@example.com']);
    }

    public function testListsWithSearchPaginationAndSort(): void
    {
        $this->create(['email' => 'a@example.com', 'firstname' => 'Alice', 'lastname' => 'Zed']);
        $this->create(['email' => 'b@example.com', 'firstname' => 'Bob', 'lastname' => 'Young']);
        $this->create(['email' => 'c@other.org', 'firstname' => 'Carl', 'lastname' => 'Xavier']);

        $page = $this->service->list(['search' => 'example', 'sort' => 'lastname|asc', 'size' => 1, 'page' => 2]);
        self::assertSame(2, $page['total']);
        self::assertSame(2, $page['page']);
        self::assertSame(1, $page['size']);
        self::assertSame(['a@example.com'], array_column($page['items'], 'email'));

        $all = $this->service->list(['sort' => 'firstname|desc', 'size' => 500]);
        self::assertSame(100, $all['size']);
        self::assertSame(['Carl', 'Bob', 'Alice'], array_column($all['items'], 'firstname'));
    }

    public function testRejectsUnknownSortField(): void
    {
        $this->expectException(UserValidationException::class);
        $this->service->list(['sort' => 'password_hash']);
    }

    public function testUpdatesOnlyProvidedFields(): void
    {
        $user = $this->create();
        $updated = $this->service->update($user['id'], ['lastname' => 'Smith', 'email' => 'jane.smith@example.com', 'password' => 'new-password']);

        self::assertSame('Jane', $updated['firstname']);
        self::assertSame('Smith', $updated['lastname']);
        self::assertSame('Jane Smith', $updated['name']);
        self::assertSame('jane.smith@example.com', $updated['email']);
        self::assertSame('jane.smith@example.com', $this->db->value('SELECT value FROM auth_user_provider'));
        $hash = $this->db->value('SELECT password_hash FROM auth_user');
        self::assertTrue((new PasswordEncoder())->verify('new-password', $hash));
    }

    public function testUpdateRejectsEmailOfAnotherUser(): void
    {
        $this->create(['email' => 'other@example.com']);
        $user = $this->create();
        $this->expectException(UserValidationException::class);
        $this->service->update($user['id'], ['email' => 'other@example.com']);
    }

    public function testAdministratorCannotChangeOwnRolesOrDeleteThemselves(): void
    {
        $admin = $this->create(['roles' => ['admin']]);
        try {
            $this->service->update($admin['id'], ['roles' => []], $admin['id']);
            self::fail('Expected a forbidden operation.');
        } catch (UserOperationForbiddenException) {
        }
        // Unchanged roles and other fields are accepted.
        $updated = $this->service->update($admin['id'], ['roles' => ['admin'], 'firstname' => 'Ann'], $admin['id']);
        self::assertSame('Ann', $updated['firstname']);

        $this->expectException(UserOperationForbiddenException::class);
        $this->service->delete($admin['id'], $admin['id']);
    }

    public function testManagerCannotEscalatePrivileges(): void
    {
        $this->roles->create(['id' => 'manager', 'label' => 'Manager', 'permissions' => ['users.manage', 'event.view']], ['users.manage', 'event.view']);
        $this->roles->create(['id' => 'treasurer', 'label' => 'Treasurer', 'permissions' => ['billing.view']], ['billing.view']);
        $this->roles->create(['id' => 'events', 'label' => 'Events', 'permissions' => ['event.view']], ['event.view']);
        $manager = $this->create(['email' => 'manager@example.com', 'roles' => ['manager']]);
        $admin = $this->create(['email' => 'admin@example.com', 'roles' => ['admin']]);

        // Grants a role within the manager's permissions.
        $user = $this->service->create(['email' => 'u@example.com', 'firstname' => 'U', 'lastname' => 'U', 'roles' => ['events']], $manager['id']);
        self::assertSame(['events'], array_column($user['roles'], 'id'));

        $attempts = [
            'grant a role beyond own permissions' => fn () => $this->service->update($user['id'], ['roles' => ['treasurer']], $manager['id']),
            'grant admin' => fn () => $this->service->update($user['id'], ['roles' => ['admin']], $manager['id']),
            'create an admin' => fn () => $this->service->create(['email' => 'x@example.com', 'firstname' => 'X', 'lastname' => 'X', 'roles' => ['admin']], $manager['id']),
            'edit a more privileged user' => fn () => $this->service->update($admin['id'], ['password' => 'taken-over!'], $manager['id']),
            'delete a more privileged user' => fn () => $this->service->delete($admin['id'], $manager['id']),
            'change own roles' => fn () => $this->service->update($manager['id'], ['roles' => ['manager', 'events']], $manager['id']),
        ];
        foreach ($attempts as $name => $attempt) {
            try {
                $attempt();
                self::fail("Expected a forbidden operation: {$name}.");
            } catch (UserOperationForbiddenException) {
                self::addToAssertionCount(1);
            }
        }

        // The administrator can do everything.
        $promoted = $this->service->update($user['id'], ['roles' => ['treasurer', 'admin']], $admin['id']);
        self::assertSame(['admin', 'treasurer'], array_column($promoted['roles'], 'id'));
        $this->service->delete($manager['id'], $admin['id']);
        self::assertSame([], $this->roles->userRoleIds($manager['id']));
    }

    public function testListIncludesRoles(): void
    {
        $this->create(['roles' => ['admin']]);
        $page = $this->service->list();
        self::assertSame([['id' => 'admin', 'label' => 'Administrator']], $page['items'][0]['roles']);
    }

    public function testDeleteRemovesUserAndRevokesTokens(): void
    {
        $user = $this->create();
        $other = $this->create(['email' => 'other@example.com']);
        $this->db->insert('auth_oauth_access_token', ['id' => 'at1', 'user_id' => $user['id']]);
        $this->db->insert('auth_oauth_access_token', ['id' => 'at2', 'user_id' => $other['id']]);
        $this->db->insert('auth_oauth_refresh_token', ['id' => 'rt1', 'access_token_id' => 'at1']);
        $this->db->insert('auth_oauth_refresh_token', ['id' => 'rt2', 'access_token_id' => 'at2']);
        $this->db->insert('auth_oauth_auth_code', ['id' => 'ac1', 'user_id' => $user['id']]);

        $this->service->delete($user['id'], 'admin-id');

        self::assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM auth_user'));
        self::assertSame('1', (string) $this->db->value("SELECT revoked FROM auth_oauth_access_token WHERE id = 'at1'"));
        self::assertSame('1', (string) $this->db->value("SELECT revoked FROM auth_oauth_refresh_token WHERE id = 'rt1'"));
        self::assertSame('1', (string) $this->db->value("SELECT revoked FROM auth_oauth_auth_code WHERE id = 'ac1'"));
        self::assertSame('0', (string) $this->db->value("SELECT revoked FROM auth_oauth_access_token WHERE id = 'at2'"));
        self::assertSame('0', (string) $this->db->value("SELECT revoked FROM auth_oauth_refresh_token WHERE id = 'rt2'"));

        $this->expectException(UserNotFoundException::class);
        $this->service->get($user['id']);
    }
}
