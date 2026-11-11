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
            ['state' => 'CO', 'discipline'=>'RN', 'visit_cycle'=>'60' ],
            ['state' => 'CO', 'discipline'=>'LVN', 'visit_cycle'=>'60' ],
            ['state' => 'CO', 'discipline'=>'LPN', 'visit_cycle'=>'60' ],
            ['state' => 'CO', 'discipline'=>'CNA', 'visit_cycle'=>'60' ],
            ['state' => 'CO', 'discipline'=>'IHSS', 'visit_cycle'=>'90' ],
        ]);
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
};
