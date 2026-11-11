<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Intake extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_intake', function (Blueprint $table) {
            $table->id('id');
            $table->string('PatientAccount', 50)->nullable()->default('');
            //$table->integer('BillingInternalPatientID')->nullable();
            $table->string('Patient_StatusID', 50)->nullable()->default('');
            $table->integer('IntakeID')->nullable();
            //$table->integer('PrimaryPayerID')->nullable();
            //$table->integer('PrimaryPayerProgramTypeID')->nullable();
            //$table->integer('PrimaryPayerGroupID')->nullable();
            $table->string('Patient_Salutation', 50)->nullable()->default('');
            $table->string('Patient_FirstName', 50)->nullable()->default('');
            $table->string('Patient_MiddleName', 50)->nullable()->default('');
            $table->string('Patient_LastName', 50)->nullable()->default('');
            $table->string('Patient_Gender', 20)->nullable()->default('');
            $table->string('Patient_Address1', 50)->nullable()->default('');
            $table->string('Patient_Address2', 50)->nullable()->default('');
            $table->string('Patient_City', 50)->nullable()->default('');
            $table->string('Patient_Zip', 10)->nullable()->default('');
            $table->string('Patient_Phone', 20)->nullable()->default('');
            $table->string('Patient_MobilPhone', 20)->nullable()->default('');
            //$table->date('Patient_DOB')->nullable();
            $table->integer('CaseManagerID')->nullable();
            $table->integer('AttendingPhysicianID')->nullable();
            $table->integer('PrimaryClinicianID')->nullable();
            $table->integer('PrimaryLVNClinicianID')->nullable();
            $table->integer('PrimaryOTID')->nullable();
            $table->integer('PrimarySTID')->nullable();
            $table->integer('PrimaryPT')->nullable();
            $table->integer('FacilityID')->nullable();
            $table->boolean('IsLatestAdmit')->nullable();
            $table->integer('AdmitNo')->nullable();
            $table->timestamp('SOCDate')->nullable();
            //$table->string('AssessmentSequence',250)->nullable()->default('');
            $table->timestamp('IntakeDate')->nullable();
            $table->timestamp('AcceptedDate')->nullable();
            $table->timestamp('NonAdmitDate')->nullable();
            $table->timestamp('InitialSOCdateCurrentAdmit')->nullable();
            $table->timestamp('DischargeDate')->nullable();
            $table->timestamp('TransferDate')->nullable();
            $table->integer('LengthOfStay')->nullable();
            $table->integer('SalesPersonID')->nullable();
            //$table->string('MedicaidNumber',50)->nullable()->default('');
            //$table->timestamp('CreatedOn')->nullable();
            $table->integer('EMRID')->nullable();
            $table->integer('LOBID')->nullable();
            $table->integer('Patient_CurrentStatusID')->nullable();
            $table->boolean('IsNonAdmit')->nullable();
            $table->string('PatientName', 100)->nullable()->default('');
            $table->timestamp('ReferralDate')->nullable();
            //$table->integer('DischargeReasonID')->nullable();
            //$table->integer('AdmissionSourceID')->nullable();
            $table->integer('EpisodeNumber')->nullable();
            $table->timestamp('EpisodeStartDate')->nullable();
            $table->timestamp('EpisodeEndDate')->nullable();
            $table->integer('PrimaryAIDEClinicianID')->nullable();
            $table->integer('PrimaryMSWClinicianID')->nullable();
            $table->integer('ReferringProviderID')->nullable();
            $table->integer('AdmissionSourceID')->nullable();
            $table->string('Patient_State', 20)->nullable()->default('');
            $table->float('AuthorizedWeeklyHours', 8, 2)->nullable();
            $table->float('AuthorizedWeeklyVisits', 8, 2)->nullable();
            $table->float('CNAAuthorizedWeeklyHours', 8, 2)->nullable();
            $table->text('CF_ClinicianDiscipline')->nullable()->default('');
            $table->text('CF_DME')->nullable()->default('');
            $table->text('CF_LiftingRequired')->nullable()->default('');
            $table->text('CF_MeetingNurses')->nullable()->default('');
            $table->text('CF_MultipleClientHome')->nullable()->default('');
            $table->boolean('CF_NeedToHire')->nullable();
            $table->text('CF_ReferralType')->nullable()->default('');
            $table->text('CF_RespWeeklyHrs')->nullable()->default('');
            $table->text('CF_ServicesNeeded')->nullable()->default('');
            $table->text('CF_SharedAgency')->nullable()->default('');
            $table->text('CF_Skills')->nullable()->default('');
            $table->text('CF_Specialized')->nullable()->default('');
            $table->text('CF_TerminalPayer')->nullable()->default('');
            $table->text('CF_TheSale')->nullable()->default('');
            $table->text('CF_WorkForceDevProj')->nullable()->default('');
            $table->text('PreferenceList')->nullable()->default('');
            $table->text('ClientCareCoordinator')->nullable()->default('');
            $table->text('PetsinHome')->nullable()->default('');
            $table->text('PetsNurseCannotOwn')->nullable()->default('');
            $table->text('NTHNotes')->nullable()->default('');
            $table->text('Biography')->nullable()->default('');
            $table->text('DrivingDirection')->nullable()->default('');
            $table->integer('CC2')->nullable();
            $table->text('CF_NurseLanguage')->nullable()->default('');
            $table->text('Languages')->nullable()->default('');
            $table->string('CF_Weight', 50)->nullable()->default('');
            $table->integer('SOCClinicianID')->nullable();
            $table->timestamp('CF_GoalSOCDate')->nullable();
            $table->string('IntakeStatus', 100)->nullable()->default('');
            $table->integer('ReferralSourceID')->nullable();
            $table->string('Payer', 255)->nullable();
            $table->string('RespitePayer', 255)->nullable();
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
