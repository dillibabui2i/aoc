<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAfterhoursScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('afterhours_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('afterhours_shifts_id');
            $table->integer('user_id');
            $table->date('start_date')->nullable();
            $table->string('start_time')->nullable();
            $table->date('end_date')->nullable();
            $table->string('end_time')->nullable();
            $table->integer('created_by')->nullable()->comment('Users ID Ref');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('updated_by')->nullable()->comment('Users ID Ref');
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
        Schema::dropIfExists('afterhours_schedule');
    }
}
