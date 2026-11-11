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
        Schema::create('schedule_status', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_status')->nullable();
            $table->timestamps();
        });

        DB::table('schedule_status')->insert([
            ['schedule_status' => 'Approved'],
            ['schedule_status' => 'Completed'],
            ['schedule_status' => 'In_Progress'],
            ['schedule_status' => 'MissedVisit'],
            ['schedule_status' => 'Planned'],
            ['schedule_status' => 'NA'],
            ['schedule_status' => 'NO'],
            ['schedule_status' => 'Office Auth'],
            ['schedule_status' => 'Real Auth'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schedule_status');
    }
};
