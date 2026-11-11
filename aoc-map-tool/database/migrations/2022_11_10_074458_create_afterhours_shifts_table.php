<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAfterhoursShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('afterhours_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('title',100)->nullable();
            $table->unsignedBigInteger('roles_id');
            $table->text('branchid')->nullable();
            $table->string('color_filled', 6)->nullable();
            $table->string('color_open', 6)->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('repeat')->nullable();
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
        Schema::dropIfExists('afterhours_shifts');
    }
}
