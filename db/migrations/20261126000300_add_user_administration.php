<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * User administration API (/api/users):
 * - auth_user.is_admin: only administrators may manage users;
 * - "users" OAuth scope, granted to the "club" client, required by the API.
 *
 * Existing installations key scopes by "name" and clients by "client_id"
 * (the columns used by the code), while the baseline migration creates "id"
 * columns: both layouts are supported.
 */
final class AddUserAdministration extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->table('auth_user')->hasColumn('is_admin')) {
            $this->execute('ALTER TABLE auth_user ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0');
        }

        $scopeKey = $this->scopeKey();
        $this->execute("INSERT IGNORE INTO auth_oauth_scope ({$scopeKey}, description) VALUES ('users', 'Manage user accounts')");

        $clientKey = $this->clientKey();
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET scopes = CONCAT(TRIM(scopes), ' users')
            WHERE {$clientKey} = 'club' AND CONCAT(' ', scopes, ' ') NOT LIKE '% users %'
            SQL);
    }

    private function scopeKey(): string
    {
        return $this->table('auth_oauth_scope')->hasColumn('name') ? 'name' : 'id';
    }

    private function clientKey(): string
    {
        return $this->table('auth_oauth_client')->hasColumn('client_id') ? 'client_id' : 'id';
    }

    public function down(): void
    {
        $clientKey = $this->clientKey();
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET scopes = TRIM(REPLACE(CONCAT(' ', scopes, ' '), ' users ', ' '))
            WHERE {$clientKey} = 'club'
            SQL);
        $scopeKey = $this->scopeKey();
        $this->execute("DELETE FROM auth_oauth_scope WHERE {$scopeKey} = 'users'");

        if ($this->table('auth_user')->hasColumn('is_admin')) {
            $this->execute('ALTER TABLE auth_user DROP COLUMN is_admin');
        }
    }
}
