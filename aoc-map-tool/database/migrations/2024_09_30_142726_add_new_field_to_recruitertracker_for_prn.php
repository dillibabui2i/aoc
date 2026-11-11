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
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->tinyinteger('is_prn_confirmed')->default(0);
            $table->integer('prn_confirmed_by')->nullable();
            $table->datetime('prn_confirmed_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruitertracker_for_prn', function (Blueprint $table) {
            //
        });
    }
};
