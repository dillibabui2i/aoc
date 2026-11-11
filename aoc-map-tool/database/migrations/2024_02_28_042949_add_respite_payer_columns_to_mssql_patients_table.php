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
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->integer('respite_primary_payer')->nullable();
            $table->integer('respite_secondary_payer')->nullable();
            $table->integer('respite_tertiary_payer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->dropColumn('respite_primary_payer');
            $table->dropColumn('respite_secondary_payer');
            $table->dropColumn('respite_tertiary_payer');
        });
    }
};
