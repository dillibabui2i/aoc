<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToNeedToHireAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->string('ClientCareCoordinator',100)->after('IntakeCoordinator')->nullable();
            $table->string('BD_Rep',100)->after('ClientCareCoordinator')->nullable();
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
