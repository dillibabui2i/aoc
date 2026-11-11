<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentsFieldToMapsPotentialNurseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('maps_potential_nurse', 'Comments'))
        {
            Schema::table('maps_potential_nurse', function (Blueprint $table) {
                $table->string('Comments')->nullable(); 
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
        if (Schema::hasColumn('maps_potential_nurse', 'Comments'))
        {
            Schema::table('maps_potential_nurse', function (Blueprint $table)
            {
                $table->dropColumn('Comments');
            });
        }
    }
}
