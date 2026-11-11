<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewables', function (Blueprint $table) {
            $table->dateTime('nursys_mismatch_created_at')->nullable()->after('nursys_mismatch');
        });

        // Backfill the records that are already sitting in the Nursys bucket.
        // There is no stored history of when a record entered the bucket (renewables.created_at is
        // the moment the license row was synced in, last_nursys_sync is overwritten on every
        // ProcessNursys run), so the countdown starts for everyone at the moment of the release.
        DB::table('renewables')
            ->where('nursys_mismatch', 1)
            ->whereNull('nursys_mismatch_created_at')
            ->update([
                'nursys_mismatch_created_at' => DB::raw('NOW()'),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renewables', function (Blueprint $table) {
            $table->dropColumn('nursys_mismatch_created_at');
        });
    }
};
