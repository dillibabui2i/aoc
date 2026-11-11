<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('class_categories')->insert([
            ['id' => 1, 'name' => 'Trach/Vent', 'color' => '#0010e6', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'TPN', 'color' => '#008047', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('class_categories')->whereIn('id', [1, 2])->delete();
    }
};
