<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNTHAccountToAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->string('liftingRequirements',500)->after('IntakeCoordinator')->nullable();
            $table->string('clinicianDiscipline',500)->after('liftingRequirements')->nullable();
            $table->string('skills',500)->after('clinicianDiscipline')->nullable();
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
