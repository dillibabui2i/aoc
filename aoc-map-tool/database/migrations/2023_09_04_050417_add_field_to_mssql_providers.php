<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToMssqlProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewables', function (Blueprint $table) {
            $table->string('nursys_outcome')->default('')->nullable();
            $table->integer('nursys_updated_by')->nullable();
            $table->datetime('nursys_updated_at')->nullable();
            
            
            
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
            //
        });
    }
}
