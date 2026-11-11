<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoughToReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reasons', function (Blueprint $table) {
            DB::table('reasons')->insert([
                'reason' => 'Cough',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reasons', function (Blueprint $table) {
            DB::table('reasons')->where('reason', 'Cough')->delete();
        });
    }
}
