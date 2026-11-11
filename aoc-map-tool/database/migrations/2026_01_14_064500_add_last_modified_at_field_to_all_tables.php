<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const IGNORED_TABLES = [
        'migrations',
        'failed_jobs',
        'jobs_manager',
        'password_resets',
        'roles',
        'role_has_permissions',
        'restricted_password',
        'permissions',
    ];

    public function up(): void
    {
        $database = DB::getDatabaseName();

        $tables = DB::select("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_type = 'BASE TABLE'
        ", [$database]);

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, self::IGNORED_TABLES)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'last_modified_at')) {
                DB::statement("
                    ALTER TABLE `$tableName`
                    ADD COLUMN `last_modified_at` TIMESTAMP
                        NULL
                        DEFAULT NULL
                        ON UPDATE CURRENT_TIMESTAMP
                ");
            }
        }
    }

    public function down(): void
    {
        $database = DB::getDatabaseName();

        $tables = DB::select("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_type = 'BASE TABLE'
        ", [$database]);

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (Schema::hasColumn($tableName, 'last_modified_at')) {
                DB::statement("
                    ALTER TABLE `$tableName`
                    DROP COLUMN `last_modified_at`
                ");
            }
        }
    }
};
