<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToNeedToHireAudit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Audit', function (Blueprint $table) {
            $table->float('HoursAdded',8,2)->after('ChangeDescription');
            $table->float('HoursRemoved',8,2)->after('HoursAdded');
            $table->float('NetChangeinHours',8,2)->after('HoursRemoved');
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
