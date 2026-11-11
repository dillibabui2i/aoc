<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->integer('tracker_id')->after('notes')->nullable();
            $table->integer('schedule_id')->after('notes')->nullable();
            $table->integer('patient_id')->after('notes')->nullable();
            $table->integer('candidate_id')->after('notes')->nullable();
            $table->integer('provider_id')->after('notes')->nullable();
            $table->integer('nth_id')->after('notes')->nullable();
            $table->string('activity', 255)->after('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notes', function (Blueprint $table) {
            //
        });
    }
}
