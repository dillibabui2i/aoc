<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFourAddNewFieldTriagCalls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('triageCalls', function (Blueprint $table) {
            $table->boolean('step1D')->after('previous_additional_notes')->nullable();
            $table->boolean('step2D')->after('step1D')->nullable();
            $table->boolean('step7D')->after('step2D')->nullable();
            $table->boolean('step30D')->after('step7D')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('four_add_new_field_triag_calls');
    }
}
