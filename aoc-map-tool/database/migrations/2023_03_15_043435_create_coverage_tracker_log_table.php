<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoverageTrackerLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coverage_tracker_log', function (Blueprint $table) {
            $table->id();
            $table->date('coverage_tracker_id')->nullable();
            $table->date('shift_date')->nullable();
            $table->date('filterDate')->nullable();
            $table->string('Patient_ID',100)->nullable();
            $table->string('Client_First_Name',500)->nullable();
            $table->string('Client_Last_Name',500)->nullable();
            $table->string('Name',500)->nullable();
            $table->string('Payer',200)->nullable();
            $table->string('Discipline',200)->nullable();
            $table->string('Service',200)->nullable();
            $table->string('Schedule_Time',200)->nullable();
            $table->string('Hours',200)->nullable();
            $table->string('Authorization',200)->nullable();
            $table->string('CF_Weight',200)->nullable();
            $table->string('Male_Nurse_Accepted',3)->nullable();
            $table->string('Pets_in_Home',200)->nullable();
            $table->string('Non_Smoking',3)->nullable();
            $table->string('Covid_Vaccine',3)->nullable();
            $table->string('Lift_Available',200)->nullable();
            $table->string('Attends_School',3)->nullable();
            $table->string('Client_Address',500)->nullable();
            $table->string('Client_County',500)->nullable();
            $table->string('Client_City',500)->nullable();
            $table->string('Client_ZipCode',500)->nullable();
            $table->string('State',500)->nullable();
            $table->string('Locations',500)->nullable();
            $table->string('lat',500)->nullable();
            $table->string('lng',500)->nullable();
            $table->boolean('needs_geocoding')->nullable()->default(1);
            $table->double('escalated_pay_rate', 5, 2)->nullable();
            $table->double('gp', 7, 5)->nullable();
            $table->integer('aoc_provider_id')->nullable();
            $table->datetime('date_filled')->nullable();
            $table->integer('filled_by')->nullable()->comment('User Ref');
            $table->string('approved_by',200)->nullable();
            $table->boolean('is_updated')->nullable();
            $table->boolean('is_ignored')->nullable()->default(1);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->integer('aoc_patient_id')->nullable();
            $table->integer('aoc_maps_coverageneeds_id')->nullable();
            $table->datetime('text_call_client_schedule_regulars_activity_date')->nullable();
            $table->datetime('text_call_record_clinicians_activity_date')->nullable();
            $table->datetime('text_call_prn_clinicians_from_local_prn_schedule_activity_date')->nullable();
            $table->datetime('text_blast_180_mile_radius_from_text_blast_map_activity_date')->nullable();
            $table->datetime('call_nurses_from_text_blast_map_activity_date')->nullable();
            $table->datetime('check_cover_nurses_availability_activity_date')->nullable();
            $table->integer('text_call_client_schedule_regulars_number')->nullable();
            $table->integer('text_call_record_clinicians_number')->nullable();
            $table->integer('text_call_prn_clinicians_from_loca_prn_schedule_number')->nullable();
            $table->integer('text_blast_180_mile_radius_from_text_blast_map_number')->nullable();
            $table->integer('call_nurses_from_text_blast_map_number')->nullable();
            $table->string('check_cover_nurses_availability_number',100)->nullable();
            $table->boolean('created_manually')->nullable();
            $table->string('reason_category',200)->nullable();
            $table->string('reason',200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coverage_tracker_log');
    }
}
