<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddNewReasonForTriageForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('reasons')->insert([
            ['reason'=>'Gastric tube question/concern'],
            ['reason'=>'Medication question/concern'],['reason'=>'Staffing questions/concern'],['reason'=>'Ventilator question/concern'],['reason'=>'Other']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {}
}
