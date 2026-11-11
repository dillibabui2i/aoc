<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableTraigeCall extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->string('clientRelationship',50)->nullable()->change();
            $table->string('careCoordination',500)->nullable();
            $table->text('careCoordinationInfo')->nullable();
            $table->string('whereYouGone',500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
