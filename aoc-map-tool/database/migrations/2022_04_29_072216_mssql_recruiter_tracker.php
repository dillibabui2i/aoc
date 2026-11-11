<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MssqlRecruiterTracker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_recruitertracker', function (Blueprint $table) {
            $table->id('id');
            $table->string('Candidate', 255)->nullable()->default('');
            $table->timestamp('Date_Contacted')->nullable();
            $table->string('Clinician_ID', 255)->nullable()->default('');
            $table->string('Bilingual', 255)->nullable()->default('');
            $table->string('Skillset', 255)->nullable()->default('');
            $table->string('Candidate_Current_Status', 255)->nullable()->default('');
            $table->timestamp('IPEA_Scheduled')->nullable();
            $table->timestamp('IPEA_Date')->nullable();
            $table->timestamp('PEPI_Scheduled')->nullable();
            $table->timestamp('PEPI_Date')->nullable();
            $table->string('PEPI_Time', 255)->nullable()->default('');
            $table->string('PEPI-Outcome', 255)->nullable()->default('');
            $table->timestamp('WEPI_Scheduled')->nullable();
            $table->timestamp('WEPI_Date')->nullable();
            $table->string('WEPI_Time', 255)->nullable()->default('');
            $table->string('WEPI-Outcome', 255)->nullable()->default('');
            $table->float('Total_Hours', '5', '2')->nullable();
            $table->timestamp('Client_Match_Date')->nullable();
            $table->timestamp('Candidate_Start_Date')->nullable();
            $table->string('Client_Name', 255)->nullable()->default('');
            $table->string('Client_ID', 255)->nullable()->default('');
            $table->string('Branch_Location', 255)->nullable()->default('');
            $table->string('Payor_Source', 255)->nullable()->default('');
            $table->float('Agreed_Pay_Rate', '5', '2')->nullable();
            $table->float('GP', '5', '2')->nullable();
            $table->string('Approved_By', 255)->nullable()->default('');
            $table->string('Candidate_Notes', 255)->nullable()->default('');
            $table->string('Bonus_Status', 255)->nullable()->default('');
            $table->timestamp('Bonus_Paid_Date')->nullable();
            $table->string('Recruiter', 255)->nullable()->default('');
            $table->integer('mssqlID')->nullable()->comment('This is ID field of mssql table');
            $table->timestamp('Modified')->nullable();
            $table->timestamp('Created')->nullable();
            $table->string('CreatedBy', 255)->nullable()->default('');
            $table->string('ModifiedBy', 255)->nullable()->default('');
            $table->integer('UserID');
            $table->integer('ProviderID');
            $table->integer('PatientID');
            $table->integer('FacilityID');
            $table->timestamp('Clinical_Competency_Review')->nullable();
            $table->timestamp('Training_Shift')->nullable();
            $table->string('First_Shift_Not_Worked', 255)->nullable()->default('');
            $table->timestamp('School_Set_Up_Due_Date')->nullable();
            $table->string('IPEA_Outcome', 255)->nullable()->default('');
            $table->string('Schedule', 255)->nullable()->default('');
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
