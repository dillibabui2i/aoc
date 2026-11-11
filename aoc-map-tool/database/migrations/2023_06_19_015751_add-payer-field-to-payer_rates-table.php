<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayerFieldToPayerRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payer_rates', function (Blueprint $table) {
            $table->string('payer', 255)->nullable()->after('payer_id');
            $table->boolean('is_deleted')->nullable()->default(0)->change();
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
            $table->dropColumn('payer');
        });
    }
}
