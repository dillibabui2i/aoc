<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelfEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('self_evaluations', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_provider_id')->nullable();
            $table->integer('manager')->nullable();
            $table->string('status',200)->nullable();
            $table->integer('item1_score')->nullable();
            $table->integer('item2_score')->nullable();
            $table->integer('item3_score')->nullable();
            $table->integer('item4_score')->nullable();
            $table->integer('item5_score')->nullable();
            $table->integer('item6_score')->nullable();
            $table->integer('item7_score')->nullable();
            $table->integer('item8_score')->nullable();
            $table->integer('item9_score')->nullable();
            $table->integer('item10_score')->nullable();
            $table->integer('item11_score')->nullable();
            $table->integer('item12_score')->nullable();
            $table->integer('item13_score')->nullable();
            $table->string('strengths')->nullable();
            $table->string('development_areas')->nullable();
            $table->boolean('completed_all_training')->nullable()->default(0);
            $table->boolean('subject_of_compliance')->nullable()->default(0);
            $table->boolean('commitment_to_compliance')->nullable()->default(0);
            $table->string('overall_performance')->nullable();
            $table->integer('overall_performance_score')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->boolean('is_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('self_evaluations');
    }
}
