<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableNeedToHireAccountsAudit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Accounts_Audit', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_nthaccounts_id');
            $table->integer('aoc_patient_id')->default(0);
            $table->integer('user_id')->default(0);
            $table->integer('NeedToHireAccountsID')->nullable();
			$table->string('ClientID', 25)->default('');
			$table->boolean('MatchInEMR')->default(0);
			$table->boolean('IsActive')->default(0);
			$table->string('CreatedReason', 255)->default('');
			$table->string('RemovalReason', 255)->default('');
			$table->string('ServicesNeeded', 255)->default('');
			$table->string('Recruiter', 255)->default('');
			$table->string('Status', 25)->default('');
			$table->boolean('IsPriority')->default(0);
			$table->string('ScheduleText', 255)->default('');
			$table->string('RespiteTerminalPayer', 255)->default('');
			$table->string('TerminalPayer', 255)->default('');
			$table->string('IntakeCoordinator', 8000)->default('');
            $table->timestamp('CreatedOn')->useCurrent();
			$table->integer('CreatedBy')->nullable();
            $table->timestamp('ModifiedOn')->useCurrent();
            $table->dateTime('HoursAddedOn')->nullable();
            $table->integer('ModifiedBy')->nullable();
			$table->boolean('is_deleted')->default(0);
			$table->boolean('is_updated')->default(0);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->string('liftingRequirements',500)->nullable();
            $table->string('clinicianDiscipline',500)->nullable();
            $table->string('skills',500)->nullable();
            $table->string('ClientCareCoordinator',100)->nullable();
            $table->string('BD_Rep',100)->nullable();
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
        Schema::dropIfExists('table_need_to_hire_accounts_audit');
    }
}
