<?php

namespace App\Core\Migration;

interface MigrationInterface
{
    public function up();

    public function down();
}