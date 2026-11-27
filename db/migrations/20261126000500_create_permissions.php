<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * auth_permission: catalogue of permissions, filled by the PermissionSeeder
 * from App\User\Role\Permissions (the code stays the source of truth).
 * auth_role_permission.permission now references it.
 *
 * The permissions already granted to roles are inserted first so that the
 * foreign key can be added; the seeder then sets the descriptions and removes
 * the permissions that are no longer in the catalogue.
 */
final class CreatePermissions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_permission (
                id VARCHAR(80) NOT NULL PRIMARY KEY,
                description VARCHAR(255) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $this->execute(<<<'SQL'
            INSERT IGNORE INTO auth_permission (id)
            SELECT DISTINCT permission FROM auth_role_permission
            SQL);

        $this->execute(<<<'SQL'
            ALTER TABLE auth_role_permission
                ADD CONSTRAINT role_permission_permission FOREIGN KEY (permission)
                    REFERENCES auth_permission (id) ON DELETE CASCADE ON UPDATE CASCADE
            SQL);
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE auth_role_permission DROP FOREIGN KEY role_permission_permission');
        // Index created by MySQL for the foreign key.
        if ($this->table('auth_role_permission')->hasIndexByName('role_permission_permission')) {
            $this->execute('ALTER TABLE auth_role_permission DROP INDEX role_permission_permission');
        }
        $this->execute('DROP TABLE IF EXISTS auth_permission');
    }
}
