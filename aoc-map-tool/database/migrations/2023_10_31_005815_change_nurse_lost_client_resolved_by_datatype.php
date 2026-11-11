<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeNurseLostClientResolvedByDatatype extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->integer('nurse_lost_client_resolved_by')->change();
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
            $table->bigInteger('nurse_lost_client_resolved_by')->change();
        });
    }
}
