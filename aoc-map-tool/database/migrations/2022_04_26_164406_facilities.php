<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Facilities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_facilities', function (Blueprint $table) {
            $table->id('id');
            $table->integer('FacilityID')->nullable();
            $table->integer('BillingFacilityID')->nullable();
            $table->string('FacilityName', 250)->nullable()->default('');
            $table->string('Address1', 50)->nullable()->default('');
            $table->string('Address2', 50)->nullable()->default('');
            $table->string('City', 30)->nullable()->default('');
            $table->string('State', 20)->nullable()->default('');
            $table->string('Zip', 10)->nullable()->default('');
            $table->string('Phone', 20)->nullable()->default('');
            $table->string('Mobile', 20)->nullable()->default('');
            $table->timestamp('CreatedOn')->nullable();
            $table->integer('EMRID')->nullable();
            $table->integer('RegionID')->nullable();
            $table->string('GL_Acct', 10)->nullable()->default('');
            $table->integer('AOC_FacilityClassificationID')->nullable();
            $table->integer('State_FacilityClassificationID')->nullable();
            $table->string('BranchName', 250)->nullable()->default('');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->boolean('is_updated')->default(0);
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
