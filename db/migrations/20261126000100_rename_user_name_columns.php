<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * auth_user: given_name/family_name -> firstname/lastname (names used by the
 * "user" entity and the sign-up form).
 */
final class RenameUserNameColumns extends AbstractMigration
{
    private const RENAMES = [
        'given_name' => 'firstname',
        'family_name' => 'lastname',
    ];

    public function up(): void
    {
        $table = $this->table('auth_user');
        foreach (self::RENAMES as $old => $new) {
            if ($table->hasColumn($new)) {
                continue;
            }
            if ($table->hasColumn($old)) {
                $table->renameColumn($old, $new)->update();
            } else {
                $table->addColumn($new, 'string', ['limit' => 190, 'null' => true])->update();
            }
        }
    }

    public function down(): void
    {
        $table = $this->table('auth_user');
        foreach (self::RENAMES as $old => $new) {
            if ($table->hasColumn($new) && !$table->hasColumn($old)) {
                $table->renameColumn($new, $old)->update();
            }
        }
    }
}
