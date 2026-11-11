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
        Schema::create('gross_margin_configurations', function (Blueprint $table) {
            $table->id();

            $table->integer('lob_id');
            $table->foreign('lob_id')
                ->references('LOBID')
                ->on('lob')
                ->cascadeOnDelete();

            $table->json('branch_ids')->nullable();

            $table->unsignedBigInteger('payer_id');
            $table->foreign('payer_id')
                ->references('id')
                ->on('payment_source')
                ->cascadeOnDelete();

            $table->decimal('recruiter_coverage_target', 5, 1)->nullable();
            $table->decimal('recruiter_escalated', 5, 1)->nullable();
            $table->decimal('tl_manager_escalated', 5, 1)->nullable();
            $table->decimal('tl_manager_whole_home_escalated', 5, 1)->nullable();
            $table->decimal('exec_team_escalated', 5, 1)->nullable();

            $table->decimal('coverage_escalated', 5, 1)->nullable();
            $table->decimal('coverage_tl_manager_escalated', 5, 1)->nullable();
            $table->decimal('coverage_tl_manager_whole_home_escalated', 5, 1)->nullable();
            $table->decimal('coverage_exec_team_escalated', 5, 1)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gross_margin_configurations');
    }
};
