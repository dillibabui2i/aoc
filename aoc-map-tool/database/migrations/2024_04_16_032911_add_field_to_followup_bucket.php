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
        Schema::table('ccc_followup_calls', function (Blueprint $table) {
            $table->date('base_date')->after("start_date");
            $table->integer('IsPriority')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('followup_bucket', function (Blueprint $table) {
            //
        });
    }
};
