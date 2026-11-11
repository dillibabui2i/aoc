<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesOnMssqlSchedulemasterAndServiceCodesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_codes', function (Blueprint $table) {
            $table->index(['ServiceCodeID'], 'idx_servicecodes_servicecodeid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->dropIndex('idx_servicecodes_servicecodeid');
        });
    }
}
