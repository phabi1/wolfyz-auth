<?php

declare(strict_types=1);

use App\User\Role\Permissions;
use Phinx\Seed\AbstractSeed;

/**
 * Default roles of a club, for a new installation or a development database.
 *
 * Only missing roles are created: existing roles (same identifier) are left
 * untouched, including their permissions, so administrators' changes are kept.
 * The permissions are synchronised first (PermissionSeeder), as the foreign key
 * requires them. It is not run automatically, otherwise a deliberately deleted
 * role would come back on every start:
 *
 *   vendor/bin/phinx seed:run -s RoleSeeder
 *
 * The "admin" system role is created by the migrations.
 */
final class RoleSeeder extends AbstractSeed
{
    private const ROLES = [
        'bureau' => [
            'label' => 'Membre du bureau',
            'description' => 'Consultation de la facturation, des adhésions et des événements.',
            'permissions' => [
                'billing.view', 'event.view',
                'membership.campaign.view', 'membership.request.view', 'membership.subscription.view',
            ],
        ],
        'tresorier' => [
            'label' => 'Trésorier',
            'description' => 'Gestion de la facturation.',
            'permissions' => [
                'billing.view', 'billing.edit',
                'membership.campaign.view', 'membership.request.view', 'membership.subscription.view',
            ],
        ],
        'secretaire' => [
            'label' => 'Secrétaire',
            'description' => 'Gestion des adhésions.',
            'permissions' => [
                'event.view',
                'membership.campaign.view', 'membership.request.view', 'membership.subscription.view',
                'membership.campaign.edit', 'membership.request.edit', 'membership.subscription.edit',
            ],
        ],
        'evenements' => [
            'label' => 'Responsable événements',
            'description' => 'Gestion des événements.',
            'permissions' => ['event.view', 'event.edit'],
        ],
    ];

    /** The permissions must exist in auth_permission (foreign key). */
    public function getDependencies(): array
    {
        return ['PermissionSeeder'];
    }

    public function run(): void
    {
        $this->synchronisePermissions();
        $connection = $this->getAdapter()->getConnection();
        $created = [];

        foreach (self::ROLES as $id => $role) {
            $unknown = array_diff($role['permissions'], Permissions::ids());
            if ($unknown !== []) {
                throw new \LogicException(sprintf('Role "%s": unknown permissions %s.', $id, implode(', ', $unknown)));
            }
            if ($this->fetchRow('SELECT id FROM auth_role WHERE id = ' . $connection->quote($id))) {
                continue;
            }

            $this->table('auth_role')->insert([
                'id' => $id,
                'label' => $role['label'],
                'description' => $role['description'],
                'is_system' => 0,
            ])->saveData();
            $this->table('auth_role_permission')->insert(array_map(
                fn (string $permission) => ['role_id' => $id, 'permission' => $permission],
                $role['permissions'],
            ))->saveData();
            $created[] = $id;
        }

        $this->getOutput()->writeln(sprintf(
            ' == %d role(s) created%s, %d already present',
            count($created),
            $created === [] ? '' : ' (' . implode(', ', $created) . ')',
            count(self::ROLES) - count($created),
        ));
    }

    /**
     * Phinx only honours getDependencies() when every seeder runs: with
     * "-s RoleSeeder" alone, PermissionSeeder would be skipped.
     */
    private function synchronisePermissions(): void
    {
        require_once __DIR__ . '/PermissionSeeder.php';
        $seeder = new PermissionSeeder();
        $seeder->setEnvironment($this->getEnvironment());
        $seeder->setAdapter($this->getAdapter());
        $seeder->setInput($this->getInput());
        $seeder->setOutput($this->getOutput());
        $seeder->run();
    }
}
