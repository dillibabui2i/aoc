<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `originating_state_id` was meant to point at a states dictionary table that will not be created.
     * The column now stores the 2-char state abbreviation directly (keys of config('constant.states')),
     * NULL = federal. Raw ALTER: renaming + retyping in one statement keeps the index on the column.
     * Existing integer values were never a real reference (no states table), so they are cleared.
     */
    public function up(): void
    {
        DB::table('immunization_recommendations')->update(['originating_state_id' => null]);

        DB::statement(
            'ALTER TABLE immunization_recommendations
                CHANGE originating_state_id originating_state VARCHAR(2) NULL,
                RENAME INDEX immunization_recommendations_originating_state_id_index
                    TO immunization_recommendations_originating_state_index'
        );
    }

    public function down(): void
    {
        DB::table('immunization_recommendations')->update(['originating_state' => null]);

        DB::statement(
            'ALTER TABLE immunization_recommendations
                CHANGE originating_state originating_state_id INT UNSIGNED NULL,
                RENAME INDEX immunization_recommendations_originating_state_index
                    TO immunization_recommendations_originating_state_id_index'
        );
    }
};
