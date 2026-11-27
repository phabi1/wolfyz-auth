<?php

namespace App\User\Role;

/**
 * Catalogue of the permissions that can be granted through roles.
 *
 * Adding a permission only requires a new entry here: the "admin" system role
 * automatically has every permission of this list.
 */
final class Permissions
{
    public const USERS_MANAGE = 'users.manage';
    public const ROLES_MANAGE = 'roles.manage';

    /** permission => English description (translated by the clients) */
    public const ALL = [
        self::USERS_MANAGE => 'Manage user accounts and their roles',
        self::ROLES_MANAGE => 'Manage roles and their permissions',
        'billing.view' => 'View billing',
        'billing.edit' => 'Edit billing',
        'membership.view' => 'View memberships',
        'membership.edit' => 'Edit memberships',
        'event.view' => 'View events',
        'event.edit' => 'Edit events',
    ];

    /** @return string[] */
    public static function ids(): array
    {
        return array_keys(self::ALL);
    }

    public static function exists(string $permission): bool
    {
        return isset(self::ALL[$permission]);
    }
}
