<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobidToCandidateApplications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidate_applications', function (Blueprint $table) {
            $table->string('job_eId', 10)->nullable()->default('');
            $table->integer('job_id')->after('id')->default(0);
            $table->date('application_date')->after('eId');
            $table->string('emailLanguage',255)->nullable();
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
