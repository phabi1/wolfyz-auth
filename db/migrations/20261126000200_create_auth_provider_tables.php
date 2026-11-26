<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Authentication providers ("local", Google, Microsoft, ...) and the link
 * between a user and the providers they can sign in with.
 *
 * Raw SQL on purpose: each table is created by a single statement (MySQL DDL
 * is not transactional) and inherits the same default collation as auth_user,
 * which the foreign keys require.
 */
final class CreateAuthProviderTables extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_provider (
                id VARCHAR(80) NOT NULL PRIMARY KEY,
                title VARCHAR(190) NOT NULL,
                provider_type VARCHAR(80) NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_user_provider (
                user_id VARCHAR(64) NOT NULL,
                auth_provider_id VARCHAR(80) NOT NULL,
                value VARCHAR(190) NOT NULL,
                PRIMARY KEY (user_id, auth_provider_id),
                CONSTRAINT user_provider_user FOREIGN KEY (user_id) REFERENCES auth_user (id) ON DELETE CASCADE,
                CONSTRAINT user_provider_provider FOREIGN KEY (auth_provider_id) REFERENCES auth_provider (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);

        $this->execute(<<<'SQL'
            INSERT IGNORE INTO auth_provider (id, title, provider_type, active) VALUES
                ('local', 'Email & password', 'local', 1)
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS auth_user_provider');
        $this->execute('DROP TABLE IF EXISTS auth_provider');
    }
}
