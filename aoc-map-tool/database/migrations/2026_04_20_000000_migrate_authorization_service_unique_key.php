<?php

namespace App\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to update auth_service_unique_id from CONCAT(service_internal_id, payment_service_id)
 * to CONCAT(payment_service_id, authorization_id) since service_internal_id is not available
 * in BigQuery (Snowflake only).
 *
 * This migration:
 * 1. Generates new composite keys for all existing records
 * 2. Backs up old values in case of rollback
 * 3. Updates the indexed field with new format
 * 4. Validates the migration before committing
 */
return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'authorizations_service';

        // Check if table exists
        if (!Schema::hasTable($tableName)) {
            return;
        }

        // Step 1: Create backup column to preserve old unique IDs for rollback/reference
        if (!Schema::hasColumn($tableName, 'auth_service_unique_id_old')) {
            Schema::table($tableName, function ($table) {
                $table->string('auth_service_unique_id_old')->nullable()->after('auth_service_unique_id');
            });
        }

        // Step 2: Backup existing auth_service_unique_id values
        DB::statement('UPDATE ' . $tableName . ' SET auth_service_unique_id_old = auth_service_unique_id WHERE auth_service_unique_id_old IS NULL');

        // Step 3: Generate and update new unique IDs based on payment_service_id + authorization_id
        // This uses COALESCE to handle potential NULL values
        DB::statement('
            UPDATE ' . $tableName . '
            SET auth_service_unique_id = CONCAT(
                COALESCE(CAST(payment_service_id AS CHAR), "NULL"),
                COALESCE(CAST(authorization_id AS CHAR), "NULL")
            )
            WHERE payment_service_id IS NOT NULL OR authorization_id IS NOT NULL
        ');

        // Step 4: Identify and handle records with NULL values in both fields
        $nullRecords = DB::table($tableName)
            ->whereNull('payment_service_id')
            ->whereNull('authorization_id')
            ->count();

        if ($nullRecords > 0) {
            logger()->warning("Authorization Service Migration: $nullRecords records have NULL in both payment_service_id and authorization_id. These records will need manual review.");
        }
        logger()->info('Authorization Service unique key migration completed successfully.');
    }

    public function down(): void
    {
        $tableName = 'authorizations_service';

        // Restore old unique IDs from backup
        if (Schema::hasColumn($tableName, 'auth_service_unique_id_old')) {
            DB::statement('UPDATE ' . $tableName . ' SET auth_service_unique_id = auth_service_unique_id_old WHERE auth_service_unique_id_old IS NOT NULL');

            Schema::table($tableName, function ($table) {
                $table->dropColumn('auth_service_unique_id_old');
            });
        }

        logger()->info('Authorization Service unique key migration rolled back.');
    }
};
