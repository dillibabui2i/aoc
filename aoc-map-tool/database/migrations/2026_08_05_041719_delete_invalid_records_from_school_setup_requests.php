<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('
        UPDATE `school_setup_requests` ssr
            SET ssr.is_deleted = 1,
                ssr.deleted_at = NOW()
            WHERE id in (
                    select `school_setup_requests`.`id`
                    from `school_setup_requests`
                    left join `mssql_patients` on `school_setup_requests`.`aoc_patient_id` = `mssql_patients`.`id`
                    left join `mssql_providers` on `school_setup_requests`.`aoc_provider_id` = `mssql_providers`.`id`
                    where (`mssql_patients`.`id` is null or `mssql_providers`.`id` is null
                       or `school_setup_requests`.`aoc_provider_id` is null) and `school_setup_requests`.`created_at` < "2026-05-01"
                    )'
        );
    }

    public function down()
    {
        DB::statement('UPDATE `school_setup_requests` ssr
                                SET ssr.is_deleted = 0,
                                    ssr.deleted_at = NULL
                                WHERE ssr.is_deleted = 1');
    }
};
