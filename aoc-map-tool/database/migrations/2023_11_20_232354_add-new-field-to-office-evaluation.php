<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldToOfficeEvaluation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->integer('item14_score')->nullable()->after('item13_score');
        });
        Schema::table('self_evaluations', function (Blueprint $table) {
            $table->integer('item14_score')->nullable()->after('item13_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('office_evaluations', function (Blueprint $table) {
            $table->dropColumn('item14_score');
        });
        Schema::table('self_evaluations', function (Blueprint $table) {
            $table->dropColumn('item14_score');
        });
    }
}
