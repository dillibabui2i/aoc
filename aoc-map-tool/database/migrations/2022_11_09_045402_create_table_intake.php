<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableIntake extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('intake', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_patient_id')->nullable();
            $table->integer('aoc_nthaccounts_id')->nullable();
            $table->string('referral_type',100)->nullable();
            $table->string('hospital',250)->nullable();
            $table->datetime('hospital_discharge_date')->nullable();
            $table->integer('anticipated_hours')->nullable();
            $table->datetime('goal_soc_date')->nullable();
            $table->boolean('admission_visit_scheduled')->nullable();
            $table->integer('intake_nurse_id')->nullable()->comment('ref users.id');
            $table->datetime('admission_visit_date')->nullable();
            $table->boolean('add_to_nth')->nullable();
            $table->string('kantime_url',250)->nullable();
            $table->text('notes')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
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
