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
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->string('intake_outcome')->nullable();
            $table->integer('intake_outcome_by')->nullable();
            $table->datetime('intake_outcome_at')->nullable();
            $table->text('intake_comment')->nullable();
            $table->datetime('intake_followup')->nullable();
            $table->string('intake_contact')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->dropColumn('intake_outcome');
            $table->dropColumn('intake_outcome_by');
            $table->dropColumn('intake_outcome_at');
            $table->dropColumn('intake_comment');
            $table->dropColumn('intake_followup');
            $table->dropColumn('intake_contact');
        });
    }
};
