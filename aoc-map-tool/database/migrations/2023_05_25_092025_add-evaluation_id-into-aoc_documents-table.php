<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvaluationIdIntoAocDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aoc_documents', function (Blueprint $table) {
            $table->integer('evaluation_id')->nullable()->after('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aoc_documents', function (Blueprint $table) {
            $table->dropColumn('evaluation_id');
        });
    }
}
