<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->integer('platform_id')->nullable();
            $table->string('app_version',10)->nullable();
            $table->integer('user_id')->default(0);
            $table->string('name',30)->nullable();
            $table->string('os',30)->nullable();
            $table->string('model',255)->nullable();
            $table->string('udid',40)->nullable();
            $table->text('fcm_token')->nullable();
            $table->string('resolution',60)->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=inactive,1=active');
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
        Schema::dropIfExists('devices');
    }
}
