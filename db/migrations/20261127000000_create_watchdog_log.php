<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * wolf_watchdog_log: log table written by App\Core\Watchdog\Storage\DbStorage
 * (e.g. every error caught by the HTTP kernel). Without it, the first logged
 * error is fatal.
 *
 * The table is shared with the API, which writes the same table in the same
 * database: it is only created when missing, and the rollback keeps it so as
 * not to delete the API's logs.
 */
final class CreateWatchdogLog extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE IF NOT EXISTS wolf_watchdog_log (
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
        // Shared with the API: intentionally kept.
    }
}
