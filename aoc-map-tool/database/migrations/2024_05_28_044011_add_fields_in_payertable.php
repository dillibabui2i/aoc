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
        Schema::table('payment_source', function (Blueprint $table) {
            $table->integer('timely_filing')->nullable(0);
            $table->integer('contractual_payment_expectations')->nullable(0);
            $table->integer('historical_payment_norm')->nullable(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_source', function (Blueprint $table) {
            $table->dropColumn('timely_filing');
            $table->dropColumn('contractual_payment_expectations');
            $table->dropColumn('historical_payment_norm');
        });
    }
};
