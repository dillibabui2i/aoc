<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTextBlastEscalatedMarginFieldToNeedToHireChecklistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            $table->decimal('TextBlastEscalatedMargin',8,2)->after('TextBlastEscalatedRate')->nullable();
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
            $table->dropColumn('TextBlastEscalatedMargin');
        });
    }
}
