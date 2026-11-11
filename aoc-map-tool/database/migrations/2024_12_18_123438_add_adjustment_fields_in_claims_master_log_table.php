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
        Schema::table('claims_master_log', function (Blueprint $table) {
            $table->integer('assigned_to')->after('followedup_at')->nullable();
            $table->string('adjustment_status')->after('assigned_to')->nullable();
            $table->string('category')->nullable();
            $table->integer('biller')->nullable();
            $table->integer('teamlead_approved_by')->nullable();
            $table->timestamp('teamlead_approved_at')->nullable();
            $table->integer('manager_approved_by')->nullable();
            $table->timestamp('manager_approved_at')->nullable();
            $table->integer('director_approved_by')->nullable();
            $table->timestamp('director_approved_at')->nullable();
            $table->integer('vp_approved_by')->nullable();
            $table->timestamp('vp_approved_at')->nullable();
            $table->integer('operations_approved_by')->nullable();
            $table->timestamp('operations_approved_at')->nullable();
            $table->integer('adjustment_completed_by')->nullable();
            $table->timestamp('adjustment_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claims_master_log', function (Blueprint $table) {
            //
        });
    }
};
