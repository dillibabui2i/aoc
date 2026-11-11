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
        Schema::table('claims_master', function (Blueprint $table) {
            $table->integer('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_level')->nullable();
        });
        Schema::table('claims_master_log', function (Blueprint $table) {
            $table->integer('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claims_master', function (Blueprint $table) {
            //
        });
    }
};
