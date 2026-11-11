<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientWellChildVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_well_child_visits', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_patient_id');
            $table->integer('primary_care_physician_id');
            $table->date('last_visit');
            $table->date('next_visit');
            $table->bigInteger('created_by')->comment('Users ID Ref who created the record');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_well_child_visits');
    }
}
