<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAocProviderIdToTriageCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->integer('aoc_provider_id')->after('aoc_patient_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->dropColumn('aoc_provider_id');
        });
    }
}
