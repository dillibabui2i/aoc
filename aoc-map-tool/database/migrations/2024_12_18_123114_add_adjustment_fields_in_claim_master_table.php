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
        Schema::table('claims_master', function (Blueprint $table) {
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
        Schema::table('claims_master', function (Blueprint $table) {
            $table->dropColumn('biller');
            $table->dropColumn('teamlead_approved_by');
            $table->dropColumn('teamlead_approved_at');
            $table->dropColumn('manager_approved_by');
            $table->dropColumn('manager_approved_at');
            $table->dropColumn('director_approved_by');
            $table->dropColumn('director_approved_at');
            $table->dropColumn('vp_approved_by');
            $table->dropColumn('vp_approved_at');
            $table->dropColumn('operations_approved_by');
            $table->dropColumn('operations_approved_at');
            $table->dropColumn('adjustment_completed_by');
            $table->dropColumn('adjustment_completed_at');
        });
    }
};
