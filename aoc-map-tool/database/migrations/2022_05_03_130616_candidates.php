<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Candidates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('eId');
            $table->string('email')->nullable()->default('');
            $table->string('companyName')->nullable()->default('');
            $table->string('firstName')->nullable()->default('');
            $table->string('lastName')->nullable()->default('');
            $table->string('title')->nullable()->default('');
            $table->string('address')->nullable()->default('');
            $table->string('address2')->nullable()->default('');
            $table->string('countryName', 50)->nullable()->default('');
            $table->string('country', 10)->nullable()->default('');
            $table->string('countryCode', 10)->nullable()->default('');
            $table->string('stateName', 50)->nullable()->default('');
            $table->string('stateCode', 10)->nullable()->default('');
            $table->string('state', 10)->nullable()->default('');
            $table->string('city', 50)->nullable()->default('');
            $table->string('homePhone', 20)->nullable()->default('');
            $table->string('location')->nullable()->default('');
            $table->string('mobile', 20)->nullable()->default('');
            $table->string('postalCode', 10)->nullable()->default('');
            $table->string('workPhone', 20)->nullable()->default('');
            $table->string('workStatus', 20)->nullable()->default('');
            $table->json('application')->nullable()->default('');
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
        //
    }
}
