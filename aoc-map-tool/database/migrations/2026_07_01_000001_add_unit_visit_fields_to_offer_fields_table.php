<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('offer_fields')->insert([
            ['key' => '1_unit_visit', 'label' => '1-Unit Visit', 'value_type' => 'int', 'created_at' => $now, 'updated_at' => $now, 'last_modified_at' => $now],
            ['key' => '2_unit_visit', 'label' => '2-Unit Visit', 'value_type' => 'int', 'created_at' => $now, 'updated_at' => $now, 'last_modified_at' => $now],
            ['key' => '3_unit_visit', 'label' => '3-Unit Visit', 'value_type' => 'int', 'created_at' => $now, 'updated_at' => $now, 'last_modified_at' => $now],
            ['key' => '4_unit_visit', 'label' => '4-Unit Visit', 'value_type' => 'int', 'created_at' => $now, 'updated_at' => $now, 'last_modified_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('offer_fields')->whereIn('key', ['1_unit_visit', '2_unit_visit', '3_unit_visit', '4_unit_visit'])->delete();
    }
};
