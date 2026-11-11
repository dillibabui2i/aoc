<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeniedByAndDeniedAtToPayerRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payer_rates', function (Blueprint $table) {
            $table->integer('denied_by')->nullable();
            $table->timestamp('denied_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payer_rates', function (Blueprint $table) {
            $table->dropColumn('denied_by');
            $table->dropColumn('denied_at');
        });
    }
}
