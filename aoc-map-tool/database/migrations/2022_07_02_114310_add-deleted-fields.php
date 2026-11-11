<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('mssql_providers', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('mssql_facilities', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('mssql_employmentstatus', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('mssql_patientstatus', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('candidate_applications', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('branch_locations', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('map_configurations', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('recruiting_schedule', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0);
        });
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->boolean('is_active')->default(1)->after('id');
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
