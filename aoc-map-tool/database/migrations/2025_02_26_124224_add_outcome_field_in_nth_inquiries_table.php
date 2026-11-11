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
        Schema::table('nth_inquiries', function (Blueprint $table) {
            $table->string('outcome')->after('Status')->nullable();
            $table->text('outcome_comment')->after('outcome')->nullable();
            $table->integer('attempt_count')->after('outcome_comment')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->integer('resolved_by')->default(0);
            $table->timestamp('convertedToLead_at')->nullable();
            $table->integer('convertedToLead_by')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nth_inquiries', function (Blueprint $table) {
            $table->dropColumn('outcome');
            $table->dropColumn('outcome_comment');
            $table->dropColumn('attempt_count');
            $table->dropColumn('convertedToLead_at');
            $table->dropColumn('convertedToLead_by');
            $table->dropColumn('resolved_at');
            $table->dropColumn('resolved_by');
        });
    }
};
