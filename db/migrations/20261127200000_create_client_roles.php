<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Roles of the OAuth clients acting on their own behalf (client_credentials
 * grant): their access tokens carry the permissions of these roles.
 *
 * The WordPress site calls the club API with such a token (it used to send a
 * shared API key):
 * - new permissions membership.registration and event.registration (public
 *   registration forms of the website);
 * - new role "site" (Site web) with these permissions;
 * - the "wordpress" client, if present, gets the client_credentials grant,
 *   the "api" scope and the "site" role.
 *
 * No foreign key to auth_oauth_client: older installations have another
 * schema for it. Self-contained: production runs the migrations, not the
 * PermissionSeeder.
 */
final class CreateClientRoles extends AbstractMigration
{
    private const PERMISSIONS = [
        'membership.registration' => 'Use the membership registration form (website)',
        'event.registration' => 'Use the event registration form (website)',
    ];

    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_oauth_client_role (
                client_id VARCHAR(80) NOT NULL,
                role_id VARCHAR(80) NOT NULL,
                PRIMARY KEY (client_id, role_id),
                INDEX client_role_role (role_id),
                CONSTRAINT client_role_role FOREIGN KEY (role_id) REFERENCES auth_role (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $quote = fn (string $value): string => $this->getAdapter()->getConnection()->quote($value);

        foreach (self::PERMISSIONS as $id => $description) {
            $this->execute(sprintf(
                'INSERT INTO auth_permission (id, description) VALUES (%s, %s)'
                . ' ON DUPLICATE KEY UPDATE description = VALUES(description)',
                $quote($id),
                $quote($description),
            ));
        }

        $this->execute(<<<'SQL'
            INSERT IGNORE INTO auth_role (id, label, description, is_system) VALUES
                ('site', 'Site web', 'Formulaires d''inscription du site web (client OAuth wordpress).', 0)
            SQL);
        foreach (array_keys(self::PERMISSIONS) as $id) {
            $this->execute(sprintf(
                "INSERT IGNORE INTO auth_role_permission (role_id, permission) VALUES ('site', %s)",
                $quote($id),
            ));
        }

        $clientKey = $this->table('auth_oauth_client')->hasColumn('client_id') ? 'client_id' : 'id';
        if (!$this->fetchRow("SELECT 1 FROM auth_oauth_client WHERE {$clientKey} = 'wordpress'")) {
            return;
        }
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET grant_types = CONCAT(TRIM(grant_types), ',client_credentials')
            WHERE {$clientKey} = 'wordpress' AND CONCAT(',', grant_types, ',') NOT LIKE '%,client_credentials,%'
            SQL);
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET scopes = CONCAT(TRIM(scopes), ' api')
            WHERE {$clientKey} = 'wordpress' AND CONCAT(' ', scopes, ' ') NOT LIKE '% api %'
            SQL);
        $this->execute("INSERT IGNORE INTO auth_oauth_client_role (client_id, role_id) VALUES ('wordpress', 'site')");
    }

    public function down(): void
    {
        $clientKey = $this->table('auth_oauth_client')->hasColumn('client_id') ? 'client_id' : 'id';
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET grant_types = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', grant_types, ','), ',client_credentials,', ','))
            WHERE {$clientKey} = 'wordpress'
            SQL);
        $this->execute(<<<SQL
            UPDATE auth_oauth_client
            SET scopes = TRIM(REPLACE(CONCAT(' ', scopes, ' '), ' api ', ' '))
            WHERE {$clientKey} = 'wordpress'
            SQL);
        $this->execute('DROP TABLE IF EXISTS auth_oauth_client_role');
        $this->execute("DELETE FROM auth_role WHERE id = 'site'");
        $this->execute("DELETE FROM auth_permission WHERE id IN ('membership.registration', 'event.registration')");
    }
}
