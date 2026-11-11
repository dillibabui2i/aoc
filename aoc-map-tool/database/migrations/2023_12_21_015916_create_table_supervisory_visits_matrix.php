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
        Schema::create('supervisory_visits_matrix', function (Blueprint $table) {
            $table->id();
            $table->string('state')->nullable();
            $table->string('discipline')->nullable();
            $table->string('visit_cycle')->nullable();
            $table->timestamps();
        });

        DB::table('supervisory_visits_matrix')->insert([
            ['state' => 'AZ', 'discipline'=>'RN', 'visit_cycle'=>'365' ],
            ['state' => 'AZ', 'discipline'=>'LVN', 'visit_cycle'=>'182' ],
            ['state' => 'AZ', 'discipline'=>'LPN', 'visit_cycle'=>'182' ],
            ['state' => 'AZ', 'discipline'=>'LHA', 'visit_cycle'=>'60' ],

            ['state' => 'FL', 'discipline'=>'RN', 'visit_cycle'=>'60' ],
            ['state' => 'FL', 'discipline'=>'LVN', 'visit_cycle'=>'60' ],
            ['state' => 'FL', 'discipline'=>'LPN', 'visit_cycle'=>'60' ],
            ['state' => 'FL', 'discipline'=>'CNA', 'visit_cycle'=>'60' ],

            ['state' => 'NC', 'discipline'=>'RN', 'visit_cycle'=>'60' ],
            ['state' => 'NC', 'discipline'=>'LVN', 'visit_cycle'=>'60' ],
            ['state' => 'NC', 'discipline'=>'LPN', 'visit_cycle'=>'60' ],
            ['state' => 'NC', 'discipline'=>'CNA', 'visit_cycle'=>'60' ],

            ['state' => 'SC', 'discipline'=>'RN', 'visit_cycle'=>'90' ],
            ['state' => 'SC', 'discipline'=>'LVN', 'visit_cycle'=>'90' ],
            ['state' => 'SC', 'discipline'=>'LPN', 'visit_cycle'=>'90' ],

            ['state' => 'PA', 'discipline'=>'RN', 'visit_cycle'=>'365' ],
            ['state' => 'PA', 'discipline'=>'LVN', 'visit_cycle'=>'365' ],
            ['state' => 'PA', 'discipline'=>'LPN', 'visit_cycle'=>'365' ],
            ['state' => 'PA', 'discipline'=>'CNA', 'visit_cycle'=>'365' ],
            ['state' => 'PA', 'discipline'=>'AIDE', 'visit_cycle'=>'365' ],

            ['state' => 'TX', 'discipline'=>'RN', 'visit_cycle'=>'365' ],
            ['state' => 'TX', 'discipline'=>'LVN', 'visit_cycle'=>'180' ],
            ['state' => 'TX', 'discipline'=>'LPN', 'visit_cycle'=>'180' ],
            ['state' => 'TX', 'discipline'=>'AIDE', 'visit_cycle'=>'90' ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_supervisory_visits_matrix');
    }
};
