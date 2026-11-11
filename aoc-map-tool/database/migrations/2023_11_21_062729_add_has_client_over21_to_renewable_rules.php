<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasClientOver21ToRenewableRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewable_rules', function (Blueprint $table) {
            $table->enum('has_client_over21',['0', '1'])->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renewable_rules', function (Blueprint $table) {
            $table->dropColumn('has_client_over21');
        });
    }
}
