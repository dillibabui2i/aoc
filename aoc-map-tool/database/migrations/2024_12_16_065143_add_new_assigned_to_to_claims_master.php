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
            $table->integer('assigned_to')->after('followedup_at')->nullable();
            $table->string('adjustment_status')->after('assigned_to')->nullable();
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
            $table->dropColumn('assigned_to');
            $table->dropColumn('adjustment_status');
        });
    }
};
