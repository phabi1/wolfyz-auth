<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * auth_watchdog_log: log table written by App\Core\Watchdog\Storage\DbStorage
 * (e.g. every error caught by the HTTP kernel). Without it, the first logged
 * error is fatal.
 *
 * The API logs into its own wolf_watchdog_log table (created by its own
 * migrations), so the two applications' logs stay separate.
 */
final class CreateWatchdogLog extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_watchdog_log (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                data JSON NOT NULL,
                level VARCHAR(16) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX created_at (created_at),
                INDEX level (level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS auth_watchdog_log');
    }
}
