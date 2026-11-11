<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Providers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_providers', function (Blueprint $table) {
            $table->id('id');
            $table->integer('ProviderID')->nullable();
            $table->integer('BillingFacilityID')->nullable();
            $table->integer('FacilityID')->nullable();
            $table->integer('ClinicianID')->nullable();
            $table->string('Salutation', 10)->nullable()->default('');
            $table->string('FirstName', 20)->nullable()->default('');
            $table->string('MiddleName', 20)->nullable()->default('');
            $table->string('LastName', 20)->nullable()->default('');
            $table->string('Gender', 10)->nullable()->default('');
            $table->string('Address1', 50)->nullable()->default('');
            $table->string('Address2', 50)->nullable()->default('');
            $table->string('City', 30)->nullable()->default('');
            $table->string('State', 20)->nullable()->default('');
            $table->string('Zip', 10)->nullable()->default('');
            $table->string('Phone', 20)->nullable()->default('');
            $table->string('Mobile', 20)->nullable()->default('');
            $table->integer('EmploymentStatusID')->nullable();
            $table->date('DateOfBirth')->nullable();
            $table->string('PayrollID', 15)->nullable()->default('');
            $table->timestamp('HiredDate')->nullable();
            $table->timestamp('TerminatedDate')->nullable();
            $table->integer('SupervisingClinician')->nullable();
            $table->integer('PayTypeID')->nullable();
            $table->string('EmployerName', 100)->nullable()->default('');
            $table->string('DisciplineName', 10)->nullable()->default('');
            $table->boolean('IsClinician')->nullable();
            $table->boolean('IsOfficeStaff')->nullable();
            $table->string('EmployementType', 20)->nullable()->default('');
            $table->string('ProductivityPointsPerWeek', 8, 2)->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->integer('EMRID')->nullable();
            $table->string('SSN', 50)->nullable()->default('');
            $table->string('ProviderName', 200)->nullable()->default('');
            $table->string('Language', 200)->nullable()->default('');
            $table->boolean('Bilingual')->nullable();
            $table->string('AOC_Email', 200)->nullable()->default('');
            $table->string('External_Email', 200)->nullable()->default('');
            $table->boolean('CF_IsSignOnBonus')->nullable();
            $table->timestamp('CF_SignOnBonusEffectiveDate')->nullable();
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
