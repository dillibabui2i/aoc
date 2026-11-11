<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidate_applications', function (Blueprint $table) {
            $table->integer('is_trackable')->default(0)->after("is_deleted");
            $table->integer('is_complete')->default(0)->after("is_trackable");
            $table->integer('next_app_id')->default(0)->after("is_complete");
            $table->integer('latest_tracker_id')->nullable->after("next_app_id");
            $table->string('WeekStart',30)->after('application_date');
            //$table->date('next_app_date')
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidate_applications', function (Blueprint $table) {
            //
        });
    }
};
