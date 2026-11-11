<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::table('renewables')
            ->where('isMissing', 1)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '!=', '0000-00-00')
            ->where('expiration_date', '!=', '0')
            ->update(['isMissing' => 0, 'updated_by' => 1, 'updated_at' => now()]);
    }

    public function down()
    {
        // No rollback for this migration
    }
};
