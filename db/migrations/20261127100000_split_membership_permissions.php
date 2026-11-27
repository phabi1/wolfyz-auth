<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Splits membership.view / membership.edit into one permission per resource
 * (campaign, request, subscription). Roles keep the same access: each old
 * grant is replaced by the matching new ones.
 *
 * Self-contained because the production deployment runs the migrations but
 * not the PermissionSeeder (which would only refresh the descriptions).
 */
final class SplitMembershipPermissions extends AbstractMigration
{
    private const SPLIT = [
        'membership.view' => [
            'membership.campaign.view' => 'View membership campaigns, their periods, lessons and settings',
            'membership.request.view' => 'View membership requests',
            'membership.subscription.view' => 'View members and their subscriptions',
        ],
        'membership.edit' => [
            'membership.campaign.edit' => 'Edit membership campaigns, their periods, lessons and settings',
            'membership.request.edit' => 'Process membership requests',
            'membership.subscription.edit' => 'Edit members and their subscriptions',
        ],
    ];

    public function up(): void
    {
        foreach (self::SPLIT as $old => $news) {
            $this->replace([$old], $news);
        }
    }

    public function down(): void
    {
        foreach (self::SPLIT as $old => $news) {
            $description = $old === 'membership.view' ? 'View memberships' : 'Edit memberships';
            $this->replace(array_keys($news), [$old => $description]);
        }
    }

    /**
     * Grants $to to every role having one of $from, then deletes $from
     * (which revokes it from the roles: ON DELETE CASCADE).
     *
     * @param string[] $from
     * @param array<string, string> $to permission => description
     */
    private function replace(array $from, array $to): void
    {
        $quote = fn (string $value): string => $this->getAdapter()->getConnection()->quote($value);
        $fromList = implode(', ', array_map($quote, $from));

        foreach ($to as $permission => $description) {
            $this->execute(sprintf(
                'INSERT INTO auth_permission (id, description) VALUES (%s, %s)'
                . ' ON DUPLICATE KEY UPDATE description = VALUES(description)',
                $quote($permission),
                $quote($description),
            ));
            $this->execute(sprintf(
                'INSERT IGNORE INTO auth_role_permission (role_id, permission)'
                . ' SELECT DISTINCT role_id, %s FROM auth_role_permission WHERE permission IN (%s)',
                $quote($permission),
                $fromList,
            ));
        }

        $this->execute("DELETE FROM auth_permission WHERE id IN ({$fromList})");
    }
}
