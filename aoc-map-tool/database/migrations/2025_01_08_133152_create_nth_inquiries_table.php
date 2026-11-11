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
        Schema::create('nth_inquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aoc_provider_id');
            $table->string('ClinicianID');
            $table->string('best_time_to_contact')->nullable();
            $table->string('preferred_contact_method')->nullable();
            $table->text('message')->nullable();
            $table->string('type',30);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nth_inquiries');
    }
};
