<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTwoFieldsForTextBlastInChecklistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            //
            $table->float('TextBlastEscalatedRate',8,2)->after('TextBlastWorked')->nullable();
            $table->text('TextBlastContent')->after('TextBlastEscalatedRate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            //
            $table->dropColumn('TextBlastEscalatedRate');
            $table->dropColumn('TextBlastContent');
        });
    }
}
