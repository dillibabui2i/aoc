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
        Schema::create('api_client_map', function (Blueprint $table) {
            $table->id();
            $table->string('clientId', 128)->unique();
            $table->string('clientSlug', 128)->unique();
            $table->unsignedBigInteger('serviceUserId');
            $table->timestamps();

            $table->foreign('serviceUserId')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_client_map');
    }
};
