<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToMapsPotentialNurseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_potential_nurse', function (Blueprint $table) {
            $table->string('pay_expectations', 20)->nullable();
            $table->text('desired_schedule')->nullable();
            $table->text('type_of_shift')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maps_potential_nurse', function (Blueprint $table) {
            $table->dropColumn('pay_expectations');
            $table->dropColumn('desired_schedule');
            $table->dropColumn('type_of_shift');
        });
    }
}
