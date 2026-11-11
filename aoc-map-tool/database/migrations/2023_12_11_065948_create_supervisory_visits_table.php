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
        Schema::create('supervisory_visits', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_provider_id')->nullable();
            $table->integer('aoc_patient_id')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('created_by')->nullable();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->integer('updated_by')->nullable();
            $table->date('visit_date_due')->nullable();
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
            $table->boolean('sv_uploaded_to_kantime')->nullable()->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->integer('completed_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supervisory_visits');
    }
};
