<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTriageFollowupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('triageFollowup', function (Blueprint $table) {
            $table->id();
            $table->integer('triagecall_id')->nullable();
            $table->integer('aoc_patient_id')->nullable();
            $table->string('followupType')->nullable();
            $table->integer('user_id')->nullable();
            $table->date('callDate')->nullable();
            $table->time('callStartTime')->nullable();
            $table->string('callerFirstname')->nullable();
            $table->string('callerLastname')->nullable();
            $table->string('callerPhone')->nullable();
            $table->string('callerEmail')->nullable();
            $table->string('callerRelationship')->nullable();
            $table->date('clientDOB')->nullable();
            $table->json('symptomStatus')->nullable();
            $table->string('finalDisposition')->nullable();
            $table->datetime('despositionActionDate')->nullable();
            $table->boolean('wasClientHospitalized')->nullable();
            $table->datetime('hospitalizationStartDate')->nullable();
            $table->datetime('hospitalizationEndDate')->nullable();
            $table->boolean('wasMedPrescribed')->nullable();
            $table->string('drugClassification')->nullable();
            $table->boolean('hasMedicationPickedUp')->nullable();
            $table->boolean('wereNewOrderRecieived')->nullable();
            $table->string('followUpAction')->nullable();
            $table->boolean('wasDiagnosisMade')->nullable();
            $table->string('ICDCode')->nullable();
            $table->string('additionCoordination')->nullable();
            $table->integer('caseConferenceWithCaseManager')->nullable();
            $table->text('questionsRegardingPlan')->nullable();
            $table->text('additionNote')->nullable();
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
        Schema::dropIfExists('triage_followup');
    }
}
