<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToTriageCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->boolean('wereNewOrderRecieived')->nullable();
            $table->string('followUpAction')->nullable();
            $table->string('interventionType')->nullable();
            $table->json('orderDetails')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->dropColumn('wereNewOrderRecieived');
            $table->dropColumn('followUpAction');
            $table->dropColumn('interventionType');
            $table->dropColumn('orderDetails');
        });
    }
}
