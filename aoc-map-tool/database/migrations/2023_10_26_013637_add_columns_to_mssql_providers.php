<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMssqlProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->enum('nurse_lost_client', ['0', '1'])->default('0');
            $table->datetime('nurse_lost_client_resolved')->nullable();
            $table->unsignedBigInteger('nurse_lost_client_resolved_by')->nullable();
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
            $table->dropColumn('nurse_lost_client');
            $table->dropColumn('nurse_lost_client_resolved');
            $table->dropColumn('nurse_lost_client_resolved_by');
        });
    }
}
