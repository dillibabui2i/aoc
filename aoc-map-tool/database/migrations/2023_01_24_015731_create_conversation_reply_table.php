<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationReplyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation_reply', function (Blueprint $table) {
            $table->id();
            $table->integer('conversation_id');
            $table->integer('sender_id');
            $table->string('message_type',10)->default('text')->comment('message type eg text or media');
            $table->text('message');
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
        Schema::dropIfExists('conversation_reply');
    }
}
