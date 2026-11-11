<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_provider_id')->nullable();
            $table->integer('aoc_candidate_id')->nullable();
            $table->string('candidate_mobile')->nullable();
            $table->string('sid');
            $table->string('messaging_service_sid');
            $table->string('chat_service_sid')->nullable();
            $table->string('friendly_name')->nullable();
            $table->string('unique_name')->nullable();
            $table->text('api_response')->nullable();
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
        Schema::dropIfExists('conversation');
    }
}
