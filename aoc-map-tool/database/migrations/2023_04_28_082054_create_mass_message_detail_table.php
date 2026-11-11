<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMassMessageDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mass_message_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('mass_message_id');
            $table->string('receiver_mobile')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('receive_status')->nullable();
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
        Schema::dropIfExists('mass_message_detail');
    }
}
