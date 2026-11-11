<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $migration = database_path('migrations/2025_10_13_013637_extend_nth_alert_map_config.php');
        $migrationClass = require $migration;
        $migrationClass->down();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $migration = database_path('migrations/2025_10_13_013637_extend_nth_alert_map_config.php');
        $migrationClass = require $migration;
        $migrationClass->up();
    }
};
