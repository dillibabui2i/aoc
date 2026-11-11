<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('nth_inquiries', function (Blueprint $table) {
            $table->bigInteger('aoc_nthaccounts_id')->after('ClinicianID');
            $table->bigInteger('aoc_patient_id')->after('aoc_nthaccounts_id');
            $table->tinyInteger('not_interested')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
