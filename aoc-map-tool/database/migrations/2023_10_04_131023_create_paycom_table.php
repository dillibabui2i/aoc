<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaycomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paycom', function (Blueprint $table) {
            $table->id();
            $table->integer('EmployeeRegisterID')->nullable();
            $table->string("PaycomEmploymentStatus")->nullable();
            $table->string("LocationGroup")->nullable();
            $table->string("Division")->nullable();
            $table->string("Department")->nullable();
            $table->integer("ProviderID")->nullable();
            $table->string("MatchType")->nullable();
            $table->string("ClincianID",50)->nullable();
            $table->string("DOLStatus")->nullable();
            $table->string("Firstname")->nullable();
            $table->string("Lastname")->nullable();
            $table->string("Nickname")->nullable();
            $table->string("Position")->nullable();
            $table->string("State",10)->nullable();
            $table->string("PaycomFacilityName")->nullable();
            $table->string("WorkEmail")->nullable();
            $table->string("SupervisorPrimaryClincianID",50)->nullable();
            $table->string("PersonalEmail")->nullable();
            $table->datetime('UpdatedOn')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paycom');
    }
}
