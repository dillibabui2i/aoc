<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement('
            UPDATE renewables r
                JOIN (
                    SELECT
                        renewables_type,
                        aoc_provider_id,
                        ProviderID,
                        renewable_item_id,
                        isMissing,
                        MAX(id) AS keep_id
                    FROM renewables
                    GROUP BY renewables_type, aoc_provider_id, ProviderID, renewable_item_id, isMissing
                    HAVING COUNT(*) > 1
                ) dupes
                ON r.renewables_type <=> dupes.renewables_type
                    AND r.aoc_provider_id <=> dupes.aoc_provider_id
                    AND r.ProviderID <=> dupes.ProviderID
                    AND r.renewable_item_id <=> dupes.renewable_item_id
                    AND r.isMissing <=> dupes.isMissing
                SET r.is_deleted = 1
                WHERE r.id <> dupes.keep_id
                    AND r.is_deleted = 0;
        ');
    }

    public function down()
    {
    }
};
