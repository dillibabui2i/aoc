<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement('
            UPDATE supervisory_visits sv
                JOIN (
                    SELECT
                        aoc_provider_id,
                        aoc_patient_id,
                        visit_date_due,
                        last_visit,
                        MAX(updated_at) AS max_updated_at,
                        MAX(id) AS id
                    FROM supervisory_visits
                    GROUP BY aoc_provider_id, aoc_patient_id, visit_date_due, last_visit
                    HAVING COUNT(*) > 1
                ) svd
                ON sv.aoc_provider_id = svd.aoc_provider_id
                    AND sv.aoc_patient_id = svd.aoc_patient_id
                    AND sv.visit_date_due = svd.visit_date_due
                    AND sv.last_visit <=> svd.last_visit
                SET
                    sv.is_deleted = 1,
                    sv.deleted_at = NOW()
                WHERE
                    sv.id <> svd.id
                    AND (sv.updated_at < svd.max_updated_at OR sv.id < svd.id);
        ');

        DB::statement('
            DELETE sv FROM supervisory_visits sv
                JOIN (SELECT MAX(id) AS id,
                          aoc_provider_id,
                          aoc_patient_id,
                          visit_date_due,
                          last_visit
                   FROM supervisory_visits
                   WHERE is_deleted = 1
                   GROUP BY aoc_provider_id, aoc_patient_id, visit_date_due, last_visit
                   HAVING COUNT(*) > 1) svd
                ON sv.aoc_provider_id = svd.aoc_provider_id
                    AND sv.aoc_patient_id = svd.aoc_patient_id
                    AND sv.visit_date_due = svd.visit_date_due
                    AND sv.last_visit <=> svd.last_visit
                WHERE sv.is_deleted = 1 AND sv.id <> svd.id;
        ');
    }

    public function down()
    {
        DB::table('supervisory_visits')
            ->where('is_deleted', 1)
            ->update([
                'is_deleted' => 0,
            ]);
    }
};
