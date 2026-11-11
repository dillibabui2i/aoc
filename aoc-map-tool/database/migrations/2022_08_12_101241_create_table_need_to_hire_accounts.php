<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableNeedToHireAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Accounts', function (Blueprint $table) {
            $table->id();
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
            $table->integer('ModifiedBy')->nullable();
			$table->boolean('is_deleted')->default(0);
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
        Schema::dropIfExists('NeedToHire_Accounts');
    }
}
