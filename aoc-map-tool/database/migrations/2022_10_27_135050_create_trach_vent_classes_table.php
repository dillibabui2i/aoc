<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrachVentClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trach_vent_classes', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('location_id')->comment('primary key of branch_locations');
            $table->integer('instructor')->comment('primary key of users');
            $table->date('class_date')->nullable();
            $table->string('class_time')->nullable();
            $table->integer('capacity')->default(0);
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('trach_vent_classes');
    }
}
