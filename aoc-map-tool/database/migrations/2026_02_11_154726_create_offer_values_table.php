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
        Schema::create('offer_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('updated_by');
            $table->string('key');
            $table->enum('version', [0, 1]);
            $table->text('value_string')->nullable();
            $table->decimal('value_decimal')->nullable();
            $table->integer('value_int')->nullable();
            $table->dateTime('value_date')->nullable();
            $table->boolean('value_bool')->nullable();
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
        Schema::dropIfExists('offer_values');
    }
};
