<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecruitingSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruiting_schedule', function (Blueprint $table) {
            $table->id();
            $table->integer('tracker_id')->nullable()->comment('mssql_recruitertracker.id Ref');
            $table->integer('type_id')->nullable()->comment('schedule_types.id Ref');
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable();
            $table->integer('patient_id')->nullable()->comment('mssql_patients.id Ref');
            $table->integer('ClinicianID')->nullable()->comment('mssql_providers.ClinicianID Ref');
            $table->integer('candidate_id')->nullable()->comment('candidates.id Ref');
            $table->string('pay_rate', 20)->nullable()->default('');
            $table->float('gp', 5, 2)->nullable();
            $table->integer('gp_approved_by')->nullable()->comment('Users.id Ref');
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
        //
    }
}
