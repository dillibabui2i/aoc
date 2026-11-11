<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candidate_applications', function (Blueprint $table) {
            $table->id();
            $table->string('consentStatus', 100);
            $table->json('customField');
            $table->string('eId', 20);
            $table->string('gender', 50);
            $table->boolean('hasArtifacts');
            $table->string('jobviteChannel', 100);
            $table->datetime('lastUpdatedDate');
            $table->string('personalDataProcessingStatus', 100);
            $table->string('race', 100);
            $table->text('resumeContent');
            $table->string('resumeFormat', 100);
            $table->string('resumeName', 255);
            $table->datetime('sentDate');
            $table->string('source', 255);
            $table->json('job');
            $table->string('disposition', 255);
            $table->datetime('hireDate');
            $table->string('sourceType', 255);
            $table->datetime('startDate');
            $table->string('veteranStatus', 100);
            $table->string('workflowState', 255);
            $table->string('workFlowStateId', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->boolean('is_updated')->default(0);
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
