<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('polygons', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('branch_locations', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('candidate_applications', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('whiteboard', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
