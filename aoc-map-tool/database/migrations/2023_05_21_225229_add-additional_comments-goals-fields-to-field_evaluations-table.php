<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalCommentsGoalsFieldsToFieldEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('field_evaluations', function (Blueprint $table) {
            $table->text('additional_comments')->nullable()->after('comments');
            $table->text('goals')->nullable()->after('additional_comments');
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
            $table->dropColumn('additional_comments');
            $table->dropColumn('goals');
        });
    }
}
