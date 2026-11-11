<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTriageCalls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('triageCalls', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('aoc_patient_id')->nullable();
            $table->date('callDate')->nullable();
            $table->time('callStartTime')->nullable();
            $table->string('callerFirstName')->nullable();
            $table->string('callerLastName')->nullable();
            $table->string('callerPhone')->nullable();
            $table->string('callerEmail')->nullable();
            $table->enum('clientRelationship',['Parent','Nurse','Caregiver']);
            $table->date('clientDOB')->nullable();
            $table->text('bodySystem')->nullable();
            $table->text('reason')->nullable();
            $table->string('disposition')->nullable();
            $table->boolean('isAmbulanceTransfer')->nullable();
            $table->text('description')->nullable();
            $table->datetime('suggestedDatetime')->nullable();
            $table->boolean('emergencyRoom')->nullable();
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
