<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivityAndNumberFieldsToMapCoverage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps_coverageneeds', function (Blueprint $table) {
            $table->datetime('text_call_client_schedule_regulars_activity_date')->nullable();
            $table->datetime('text_call_record_clinicians_activity_date')->nullable();
            $table->datetime('text_call_prn_clinicians_from_local_prn_schedule_activity_date')->nullable();
            $table->datetime('text_blast_180_mile_radius_from_text_blast_map_activity_date')->nullable();
            $table->datetime('call_nurses_from_text_blast_map_activity_date')->nullable();
            $table->datetime('check_cover_nurses_availability_activity_date')->nullable();

            $table->integer('text_call_client_schedule_regulars_number')->nullable();
            $table->integer('text_call_record_clinicians_number')->nullable();
            $table->integer('text_call_prn_clinicians_from_local_prn_schedule_number')->nullable();
            $table->integer('text_blast_180_mile_radius_from_text_blast_map_number')->nullable();
            $table->integer('call_nurses_from_text_blast_map_number')->nullable();
            $table->string('check_cover_nurses_availability_number',100)->nullable();
        });
        Schema::table('coverage_tracker', function (Blueprint $table) {
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
