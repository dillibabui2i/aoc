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
        Schema::create('claims_master_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('claims_master_id')->nullable();
            $table->unsignedInteger('agency_id')->nullable();
            $table->unsignedInteger('agency_branch_id')->nullable();
            $table->unsignedInteger('claim_id')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('payersource_id')->nullable();
            $table->unsignedInteger('episode_id')->nullable();
            $table->text('type')->nullable();
            $table->timestamp('invoice_date')->nullable();
            $table->text('invoice_number')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('totalamount', 14, 4)->default(0.0000);
            $table->decimal('contractual_amount', 14, 4)->default(0.0000);
            $table->decimal('balance', 14, 4)->default(0.0000);
            $table->decimal('balance_contractual', 14, 4)->default(0.0000);
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('is_fully_paid')->default(false);
            $table->boolean('is_partially_paid')->default(false);
            $table->boolean('is_appealed')->default(false);
            $table->boolean('is_denied')->default(false);
            $table->decimal('contractual_adjustments', 14, 4)->default(0.0000);
            $table->decimal('other_adjustments', 14, 4)->default(0.0000);
            $table->decimal('paidamount', 14, 4)->default(0.0000);
            $table->boolean('is_secondary')->nullable();
            $table->boolean('is_tertiary')->nullable();
            $table->boolean('is_lupa')->nullable();
            $table->boolean('isoutlier')->nullable();
            $table->boolean('is_pep')->nullable();
            $table->boolean('is_transferred_copay')->nullable();
            $table->boolean('is_service_copay')->nullable();
            $table->boolean('is_manual')->nullable();
            $table->decimal('initial_hhrg', 14, 4)->default(0.0000);
            $table->decimal('final_hhrg', 14, 4)->default(0.0000);
            $table->decimal('hhrg_goal', 14, 4)->default(0.0000);
            $table->text('status')->nullable();
            $table->timestamp('paid_date')->nullable();
            $table->boolean('is_edicreated')->nullable();
            $table->timestamp('edi_createddate')->nullable();
            $table->text('edi_createdby_user_id')->nullable();
            $table->unsignedInteger('edi_file_id')->nullable();
            $table->text('createdby_user_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('is_billed')->nullable();
            $table->string('invoice_status', 55)->nullable();
            $table->unsignedInteger('parent_claimid')->nullable();
            $table->unsignedInteger('primary_claimid')->nullable();
            $table->boolean('isvalidrecord')->nullable();
            $table->timestamp('addedon')->nullable();
            $table->date('transaction_date')->nullable();
            $table->timestamp('billed_date')->nullable();
            $table->boolean('is_client_pay')->nullable();
            $table->boolean('is_locked')->nullable();
            $table->date('gl_locked_month')->nullable();
            $table->timestamp('gl_locked_date')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps(); // created_at and updated_at
            $table->unsignedInteger('updated_by')->nullable();
            $table->boolean('is_deleted')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('subreason', 255)->nullable();
            $table->string('notes', 255)->nullable();
            $table->string('subreason_details', 255)->nullable();
            $table->string('comment', 255)->nullable();
            $table->unsignedInteger('followedup_by')->nullable();
            $table->dateTime('followedup_at')->nullable();
            $table->text('remit_remark')->nullable();
            $table->timestamp('archived_at')->useCurrent();

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('claims_master_log');
    }
};
