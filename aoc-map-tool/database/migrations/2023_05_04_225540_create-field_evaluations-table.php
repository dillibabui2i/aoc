<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFieldEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('field_evaluations', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_provider_id')->nullable();
            $table->integer('manager')->nullable();
            $table->string('status',200)->nullable();
            $table->integer('item1_score')->nullable();
            $table->string('item1_note')->nullable();
            $table->integer('item2_score')->nullable();
            $table->string('item2_note')->nullable();
            $table->integer('item3_score')->nullable();
            $table->string('item3_note')->nullable();
            $table->integer('item4_score')->nullable();
            $table->string('item4_note')->nullable();
            $table->integer('item5_score')->nullable();
            $table->string('item5_note')->nullable();
            $table->integer('item6_score')->nullable();
            $table->string('item6_note')->nullable();
            $table->integer('item7_score')->nullable();
            $table->string('item7_note')->nullable();
            $table->integer('item8_score')->nullable();
            $table->string('item8_note')->nullable();
            $table->string('comments')->nullable();
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
        Schema::dropIfExists('table_for_rightsignature');
    }
}
