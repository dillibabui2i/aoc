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
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->tinyInteger('change_request_employment')
            ->default(0)->comment('0: Default, 1: Part-time, 2: Full-time');
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
            $table->dropColumn('change_request_employment');
        });
    }
};
