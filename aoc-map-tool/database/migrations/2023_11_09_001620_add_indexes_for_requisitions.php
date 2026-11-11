<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesForRequisitions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Checklist', function(Blueprint $table)
        {
            $table->index('HUBcity');
        });

        Schema::table('NeedToHire_Hub', function(Blueprint $table)
        {
            $table->index('ShortState');
            $table->index('City');
        });

        Schema::table('mssql_patients', function(Blueprint $table)
        {
            $table->index('Patient_City');
            $table->index('Patient_State');
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
