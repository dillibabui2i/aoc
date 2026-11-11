<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToNthTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Accounts', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
            $table->integer('mssql_id')->after('id');
        });
        Schema::table('NeedToHire_Hub', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('NeedToHire_ShiftType', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('created_by')->after('created_at')->nullable();
            $table->integer('updated_by')->after('updated_at')->nullable();
        });
        Schema::table('NeedToHire_ShiftTypeMapping', function (Blueprint $table) {
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
        Schema::table('nth_tables', function (Blueprint $table) {
            //
        });
    }
}
