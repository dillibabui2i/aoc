<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveAndAddIndexInCandidatesTable extends Migration
{
    /**
     * Run the migrations.
     * @author Nayan
     * @return void
     */
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            //
            $table->dropIndex('eId');
            $table->dropIndex('tempFullName');
            $table->dropIndex('candidates_jobid_index');

            $table->index(['eId'], 'eId');
            $table->index(['fullName'], 'tempFullName');
            $table->index('jobId');
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
