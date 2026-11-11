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
            $table->integer('ccc_nth_call_frequency')->default(0)->nullable();
            $table->integer('ccc_client_call_frequency')->default(0)->nullable();
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
            $table->dropColumn('ccc_nth_call_frequency');
            $table->dropColumn('ccc_client_call_frequency');
        });
    }
};
