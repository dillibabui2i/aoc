<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lob', function (Blueprint $table) {
            DB::table('lob')->insert([
                ['LOBID' => '1111', 'LOBName' => 'Respite Nursing', 'EMRID' => '1'],
                ['LOBID' => '2222', 'LOBName' => 'Respite Attendant', 'EMRID' => '1'],
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
        Schema::table('lob', function (Blueprint $table) {
            //
        });
    }
};
