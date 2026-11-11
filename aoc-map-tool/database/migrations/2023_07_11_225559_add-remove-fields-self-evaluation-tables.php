<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemoveFieldsSelfEvaluationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('self_evaluations', function($table) {
            $table->dropColumn('strengths');
            $table->dropColumn('development_areas');
            $table->dropColumn('completed_all_training');
            $table->dropColumn('subject_of_compliance');
            $table->dropColumn('commitment_to_compliance');
            $table->dropColumn('overall_performance');
            $table->dropColumn('overall_performance_score');
            $table->string('last_review_contributions')->after('item13_score');
            $table->string('performed_additional_duties')->after('last_review_contributions');
            $table->string('areas_improve')->after('performed_additional_duties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('self_evaluations', function($table) {
            $table->dropColumn('last_review_contributions');
            $table->dropColumn('performed_additional_duties');
            $table->dropColumn('areas_improve');
        });
    }
}
