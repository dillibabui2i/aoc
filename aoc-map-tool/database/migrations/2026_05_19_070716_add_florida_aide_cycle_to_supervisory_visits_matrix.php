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
        DB::table('supervisory_visits_matrix')->insert([
            ['state' => 'FL', 'discipline'=>'AIDE', 'visit_cycle'=>'60']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('supervisory_visits_matrix')
            ->where('state', 'FL')
            ->where('discipline', 'AIDE')
            ->where('visit_cycle', '60')
            ->delete();
    }
};
