<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNthaccountsHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Historical', function (Blueprint $table) {
            $table->id();
			$table->integer('aoc_nthaccounts_id')->nullable();
			$table->boolean('IsActive')->default(0);
            $table->float('ShiftHours', 8, 2);
			$table->string('CreatedReason', 255)->default('');
			$table->string('RemovalReason', 255)->default('');
            $table->date('nth_date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
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
}
