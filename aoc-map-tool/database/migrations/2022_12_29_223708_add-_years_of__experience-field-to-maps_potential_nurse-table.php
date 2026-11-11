<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYearsOfExperienceFieldToMapsPotentialNurseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('maps_potential_nurse', 'Years_of_Experience'))
        {
            Schema::table('maps_potential_nurse', function (Blueprint $table) {
                $table->string('Years_of_Experience',50)->nullable(); 
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('maps_potential_nurse', 'Years_of_Experience'))
        {
            Schema::table('maps_potential_nurse', function (Blueprint $table)
            {
                $table->dropColumn('Years_of_Experience');
            });
        }
    }
}
