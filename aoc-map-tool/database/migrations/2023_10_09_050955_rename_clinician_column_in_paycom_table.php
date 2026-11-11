<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameClinicianColumnInPaycomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paycom', function (Blueprint $table) {
            //
            $table->renameColumn('ClincianID', 'ClinicianID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paycom', function (Blueprint $table) {
            //
            $table->renameColumn('ClinicianID', 'ClincianID');
        });
    }
}
