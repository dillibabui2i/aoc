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
            $table->integer('aoc_cf_sms_status')->default(0)->index();
            $table->integer('aoc_cf_preference_status')->default(0)->index();
            $table->integer('aoc_cf_subscription_status')->default(0)->index();
        });
    }

    /**
     * Discard the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->dropColumn(['aoc_cf_sms_status', 'aoc_cf_preference_status', 'aoc_cf_subscription_status']);
        });
    }
};
