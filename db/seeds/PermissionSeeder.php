<?php

declare(strict_types=1);

use App\User\Role\Permissions;
use Phinx\Seed\AbstractSeed;

/**
 * Synchronises auth_permission with the catalogue defined in code
 * (App\User\Role\Permissions::ALL):
 * - inserts the new permissions and updates the descriptions;
 * - deletes the permissions removed from the catalogue, which also revokes
 *   them from every role (ON DELETE CASCADE).
 *
 * Idempotent; run after the migrations (the Docker entrypoint does it):
 *
 *   vendor/bin/phinx seed:run -s PermissionSeeder
 */
final class PermissionSeeder extends AbstractSeed
{
    public function run(): void
    {
        $adapter = $this->getAdapter();
        $quote = fn (string $value): string => $adapter->getConnection()->quote($value);

        $values = [];
        foreach (Permissions::ALL as $id => $description) {
            $values[] = '(' . $quote($id) . ', ' . $quote($description) . ')';
        }
        $this->execute(
            'INSERT INTO auth_permission (id, description) VALUES ' . implode(', ', $values)
            . ' ON DUPLICATE KEY UPDATE description = VALUES(description)'
        );

        $ids = implode(', ', array_map($quote, Permissions::ids()));
        $removed = $this->fetchAll("SELECT id FROM auth_permission WHERE id NOT IN ({$ids})");
        if ($removed !== []) {
            $this->execute("DELETE FROM auth_permission WHERE id NOT IN ({$ids})");
        }

        $this->getOutput()->writeln(sprintf(
            ' == %d permission(s) synchronised, %d removed%s',
            count(Permissions::ALL),
            count($removed),
            $removed === [] ? '' : ' (' . implode(', ', array_column($removed, 'id')) . ')',
        ));
    }
}
