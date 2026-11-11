<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Notifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('type', 20)->nullable();
            $table->integer('related_item')->nullable();
            $table->text('title')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('read')->default(0);
            $table->boolean('push_notification')->default(0);
            $table->datetime('snooze_until')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }
}
