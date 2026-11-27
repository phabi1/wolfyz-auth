<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * auth_oauth_client.client_secret used to be stored in clear while
 * ClientRepository::validateClient() checks it with password_verify().
 * Hash every secret that is not already a password_hash() hash.
 */
final class HashOauthClientSecrets extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('auth_oauth_client');
        if (!$table->exists() || !$table->hasColumn('client_secret')) {
            return; // older baseline schema without client_secret: nothing to hash
        }

        $rows = $this->fetchAll(
            "SELECT id, client_secret FROM auth_oauth_client WHERE client_secret IS NOT NULL AND client_secret <> ''"
        );

        foreach ($rows as $row) {
            if (password_get_info($row['client_secret'])['algo'] !== null) {
                continue; // already hashed
            }

            $this->getQueryBuilder('update')
                ->update('auth_oauth_client')
                ->set('client_secret', password_hash($row['client_secret'], PASSWORD_DEFAULT))
                ->where(['id' => $row['id']])
                ->execute();
        }
    }

    public function down(): void
    {
        // Hashes cannot be reverted to clear-text secrets.
    }
}
