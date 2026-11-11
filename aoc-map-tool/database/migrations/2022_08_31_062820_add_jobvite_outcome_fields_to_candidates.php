<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobviteOutcomeFieldsToCandidates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->datetime('FollowUpDateTime')->nullable();
            $table->string('Client', 255)->nullable();
            $table->string('Payer', 255)->nullable();
            $table->string('Pay_Rate', 255)->nullable();
            $table->float('GP', '7', '5')->nullable();
            $table->text('RejectedByAgencyReason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            //
        });
    }
}
