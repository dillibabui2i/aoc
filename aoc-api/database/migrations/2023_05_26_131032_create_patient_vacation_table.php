<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientVacationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_vacation', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_patient_id');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('destination')->nullable();
            $table->boolean('with_nurse')->default(0);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_vacation');
    }
}
