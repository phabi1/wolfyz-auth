<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Roles and permissions, replacing auth_user.is_admin.
 *
 * - auth_role: roles managed from the club application; the "admin" system
 *   role always has every permission and can be neither edited nor deleted.
 * - auth_role_permission: permissions of the other roles (the catalogue of
 *   permissions is defined in App\User\Role\Permissions).
 * - auth_user_role: roles of each user.
 * - "roles" OAuth scope (granted to the "club" client): adds the "roles" and
 *   "permissions" claims to the ID token and UserInfo.
 *
 * Users flagged is_admin receive the "admin" role before the column is dropped.
 * Raw SQL on purpose: same collation as auth_user, required by the foreign keys.
 */
final class CreateRoles extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_role (
                id VARCHAR(80) NOT NULL PRIMARY KEY,
                label VARCHAR(190) NOT NULL,
                description VARCHAR(255) NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_role_permission (
                role_id VARCHAR(80) NOT NULL,
                permission VARCHAR(80) NOT NULL,
                PRIMARY KEY (role_id, permission),
                CONSTRAINT role_permission_role FOREIGN KEY (role_id) REFERENCES auth_role (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_user_role (
                user_id VARCHAR(64) NOT NULL,
                role_id VARCHAR(80) NOT NULL,
                PRIMARY KEY (user_id, role_id),
                INDEX user_role_role (role_id),
                CONSTRAINT user_role_user FOREIGN KEY (user_id) REFERENCES auth_user (id) ON DELETE CASCADE,
                CONSTRAINT user_role_role FOREIGN KEY (role_id) REFERENCES auth_role (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $this->execute(<<<'SQL'
            INSERT IGNORE INTO auth_role (id, label, description, is_system) VALUES
                ('admin', 'Administrateur', 'Toutes les permissions, y compris la gestion des utilisateurs et des rôles.', 1)
            SQL);

        if ($this->table('auth_user')->hasColumn('is_admin')) {
            $this->execute(<<<'SQL'
                INSERT IGNORE INTO auth_user_role (user_id, role_id)
                SELECT id, 'admin' FROM auth_user WHERE is_admin = 1
                SQL);
            $this->execute('ALTER TABLE auth_user DROP COLUMN is_admin');
        }

        $scopeKey = $this->table('auth_oauth_scope')->hasColumn('name') ? 'name' : 'id';
        $this->execute("INSERT IGNORE INTO auth_oauth_scope ({$scopeKey}, description) VALUES ('roles', 'Roles and permissions')");

        $clientKey = $this->table('auth_oauth_client')->hasColumn('client_id') ? 'client_id' : 'id';
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET scopes = CONCAT(TRIM(scopes), ' roles')
            WHERE {$clientKey} = 'club' AND CONCAT(' ', scopes, ' ') NOT LIKE '% roles %'
            SQL);
    }

    public function down(): void
    {
        $clientKey = $this->table('auth_oauth_client')->hasColumn('client_id') ? 'client_id' : 'id';
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET scopes = TRIM(REPLACE(CONCAT(' ', scopes, ' '), ' roles ', ' '))
            WHERE {$clientKey} = 'club'
            SQL);
        $scopeKey = $this->table('auth_oauth_scope')->hasColumn('name') ? 'name' : 'id';
        $this->execute("DELETE FROM auth_oauth_scope WHERE {$scopeKey} = 'roles'");

        if (!$this->table('auth_user')->hasColumn('is_admin')) {
            $this->execute('ALTER TABLE auth_user ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0');
        }
        if ($this->hasTable('auth_user_role')) {
            $this->execute(<<<'SQL'
                UPDATE auth_user SET is_admin = 1
                WHERE id IN (SELECT user_id FROM auth_user_role WHERE role_id = 'admin')
                SQL);
        }

        $this->execute('DROP TABLE IF EXISTS auth_user_role');
        $this->execute('DROP TABLE IF EXISTS auth_role_permission');
        $this->execute('DROP TABLE IF EXISTS auth_role');
    }
}
