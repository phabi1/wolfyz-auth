<?php

namespace App\Tests\User;

require_once __DIR__ . '/UserTestCase.php';

use App\User\Exception\RoleNotFoundException;
use App\User\Exception\UserOperationForbiddenException;
use App\User\Exception\UserValidationException;
use App\User\Role\Permissions;
use App\User\Role\RoleService;

class RoleServiceTest extends UserTestCase
{
    private RoleService $roles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roles = $this->container->get('user.roles');
    }

    private function all(): array
    {
        return Permissions::ids();
    }

    public function testAdminSystemRoleHasEveryPermission(): void
    {
        $admin = $this->roles->get('admin');
        self::assertTrue($admin['is_system']);
        self::assertSame(Permissions::ids(), $admin['permissions']);
        self::assertSame(Permissions::ids(), $this->roles->permissionsOfRoles(['admin']));
    }

    public function testCreatesRoleWithSlugIdentifierAndCatalogueOrderedPermissions(): void
    {
        $role = $this->roles->create([
            'label' => ' Trésorier ',
            'description' => ' Finances ',
            'permissions' => ['billing.edit', 'billing.view', 'billing.view'],
        ], $this->all());

        self::assertMatchesRegularExpression('/^tr[a-z-]*sorier$/', $role['id']);
        self::assertSame('Trésorier', $role['label']);
        self::assertSame('Finances', $role['description']);
        self::assertFalse($role['is_system']);
        self::assertSame(['billing.view', 'billing.edit'], $role['permissions']);
        self::assertSame(0, $role['user_count']);

        $again = $this->roles->create(['label' => 'Trésorier'], $this->all());
        self::assertSame($role['id'] . '-2', $again['id']);
    }

    public function testValidatesInput(): void
    {
        try {
            $this->roles->create(['label' => '', 'permissions' => ['nope']], $this->all());
            self::fail('Expected a validation error.');
        } catch (UserValidationException $e) {
            self::assertSame(['label', 'permissions'], array_keys($e->getErrors()));
        }
        $this->expectException(UserValidationException::class);
        $this->roles->create(['id' => 'admin', 'label' => 'Admin bis'], $this->all());
    }

    public function testUpdatesAndDeletesRoleAndUserAssignments(): void
    {
        $role = $this->roles->create(['id' => 'events', 'label' => 'Events', 'permissions' => ['event.view']], $this->all());
        $this->roles->setUserRoles('u1', ['events']);
        self::assertSame(['event.view'], $this->roles->userPermissions('u1'));
        self::assertSame(1, $this->roles->get('events')['user_count']);

        $updated = $this->roles->update('events', ['permissions' => ['event.view', 'event.edit']], $this->all());
        self::assertSame('Events', $updated['label']);
        self::assertSame(['event.view', 'event.edit'], $this->roles->userPermissions('u1'));

        $this->roles->delete($role['id'], $this->all());
        self::assertSame([], $this->roles->userRoleIds('u1'));
        $this->expectException(RoleNotFoundException::class);
        $this->roles->get('events');
    }

    public function testSystemRoleIsReadOnly(): void
    {
        $this->expectException(UserOperationForbiddenException::class);
        $this->roles->update('admin', ['label' => 'Boss'], $this->all());
    }

    public function testCannotGrantOrTouchPermissionsBeyondOwn(): void
    {
        $this->roles->create(['id' => 'billing', 'label' => 'Billing', 'permissions' => ['billing.view']], $this->all());
        $mine = ['roles.manage', 'event.view'];

        foreach ([
            fn () => $this->roles->create(['label' => 'X', 'permissions' => ['billing.view']], $mine),
            fn () => $this->roles->update('billing', ['label' => 'Y'], $mine),
            fn () => $this->roles->delete('billing', $mine),
        ] as $attempt) {
            try {
                $attempt();
                self::fail('Expected a forbidden operation.');
            } catch (UserOperationForbiddenException) {
                self::addToAssertionCount(1);
            }
        }
        $own = $this->roles->create(['label' => 'Viewer', 'permissions' => ['event.view']], $mine);
        self::assertSame(['event.view'], $own['permissions']);
    }

    public function testRoleIdentifiersAreEscapedInQueries(): void
    {
        self::assertSame([], $this->roles->existing(["x') OR 1=1 --"]));
        self::assertSame(['admin'], $this->roles->existing(['admin', 'ghost']));
    }
}
