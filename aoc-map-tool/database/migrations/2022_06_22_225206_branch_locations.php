<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BranchLocations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branch_locations', function (Blueprint $table) {
            $table->id();
            $table->string('branch', 100)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('street_address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zipcode', 25)->nullable();
            $table->text('IPEA_instructions')->nullable();
            $table->text('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->string('lat', 25)->nullable();
            $table->string('lng', 25)->nullable();
            $table->tinyInteger('needs_geocoding')->comment('0-No,1-Yes')->default(1);
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
