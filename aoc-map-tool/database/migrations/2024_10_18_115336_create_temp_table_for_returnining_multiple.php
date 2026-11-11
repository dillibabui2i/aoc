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
        Schema::create('temp_number', function (Blueprint $table) {
            $table->id("num");
        });

        for($i=0;$i<10;$i++) {
            DB::table('temp_number')->insert(['num'=>$i+1]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_table_for_returnining_multiple');
    }
};
