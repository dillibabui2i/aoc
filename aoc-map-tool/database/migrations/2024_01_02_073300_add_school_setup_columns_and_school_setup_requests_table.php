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
            $table->boolean('needs_school_set_up')->default(false);
        });

        Schema::create('school_setup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('assigned_ccc');
            $table->string('aoc_patient_id');
            $table->date('start_date');
            $table->integer('aoc_provider_id')->nullable();
            $table->integer('tracker_id')->nullable();
            $table->string('school_contact', 250);
            $table->string('school_contact_info', 250);
            $table->boolean('school_set_up_not_needed')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('status')->default(false);
            $table->integer('created_by');
            $table->integer('updated_by');
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
        Schema::dropIfExists('school_setup_requests');

        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->dropColumn('needs_school_set_up');
        });
    }
};
