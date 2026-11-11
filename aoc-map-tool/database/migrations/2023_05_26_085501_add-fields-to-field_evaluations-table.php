<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToFieldEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_evaluations', function (Blueprint $table) {
            $table->string('productivity', 255)->nullable()->after('goals');
            $table->string('ie_re_time', 255)->nullable()->after('productivity');
            $table->string('rv_sv_dc_time', 255)->nullable()->after('ie_re_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('field_evaluations', function (Blueprint $table) {
            $table->dropColumn('productivity');
            $table->dropColumn('ie_re_time');
            $table->dropColumn('rv_sv_dc_time');
        });
    }
}
