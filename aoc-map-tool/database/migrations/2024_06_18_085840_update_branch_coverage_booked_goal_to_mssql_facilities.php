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
        $branchGoals = [
            'Abilene' => 250,
            'Austin' => 300,
            'Dallas' => 1775,
            'Fort Worth' => 1775,
            'Lubbock' => 250,
            'San Antonio' => 325,
            'Temple' => 300,
            'Wichita Falls' => 250,
            'Amarillo' => 250,
            'Corpus Christi' => 325,
            'El Paso' => 1250,
            'Houston' => 1825,
            'Odessa' => 250,
            'Sherman' => 850,
            'Tyler' => 450,
            'Denver' => 200,
            'Colorado Springs' => 200,
            'Fort Collins' => 200,
            'Pueblo' => 200,
            'Phoenix' => 200,
            'Tuscon' => 200,
            'NC - Mission Medstaff - Ashville' => 200,
            'Charlotte' => 200,
            'Winston-Salem' => 200,
            'Raleigh' => 200,
            'Wilmington' => 200,
            'Columbia' => 200,
            'PA - Allentown' => 200,
            'DuBois' => 200,
            'PA – Harrisburg' => 200,
            'PA - Pittsburgh' => 200,
            'Reading' => 200,
            'PA - Del Co' => 200,
            'FL - Gainesville' => 200,
            'Jacksonville' => 200,
            'FL - Tampa' => 200,
            'FL - Orlando' => 200,
            'Port Saint Lucie' => 200
        ];
        
        foreach ($branchGoals as $branchName => $goal) {
            DB::table('mssql_facilities')
                ->where('BranchName', $branchName)
                ->update(['branch_coverage_booked_goal' => $goal]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_facilities', function (Blueprint $table) {
            //
        });
    }
};
