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
        Schema::table('intake', function (Blueprint $table) {
            $table->string('IntakeStatus')->nullable();
            $table->string('Payer')->nullable();
            $table->string('RespitePayer')->nullable();
            $table->integer('ic_id')->nullable();
            $table->text('CF_ClinicianDiscipline')->nullable();
            $table->text('CF_ServicesNeeded')->nullable();
            $table->text('CF_Skills')->nullable();
            $table->text('NTHNotes')->nullable();
            $table->double('AuthorizedWeeklyHours', 8, 2)->nullable();
            $table->timestamp('CF_GoalSOCDate')->nullable();
            $table->timestamp('ReferralDate')->nullable();
            $table->integer('ReferralSourceID')->nullable();
            $table->integer('AttendingPhysicianID')->nullable();
            $table->string('BD_Rep')->nullable();
            $table->string('living_situation')->nullable();
            $table->integer('auth_nurse_id')->nullable();
            $table->integer('LOBID')->nullable();
            $table->string('intake_pending_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn([
                'IntakeStatus',
                'Payer',
                'RespitePayer',
                'ic_id',
                'CF_ClinicianDiscipline',
                'CF_ServicesNeeded',
                'CF_Skills',
                'NTHNotes',
                'AuthorizedWeeklyHours',
                'CF_GoalSOCDate',
                'ReferralDate',
                'ReferralSourceID',
                'AttendingPhysicianID',
                'BD_Rep',
                'living_situation',
                'auth_nurse_id',
                'LOBID',
                'intake_pending_reason'
            ]);
        });
    }
};
