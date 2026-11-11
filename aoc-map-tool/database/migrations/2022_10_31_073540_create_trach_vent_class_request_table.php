<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrachVentClassRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trach_vent_class_request', function (Blueprint $table) {
            $table->id();
            $table->integer('trach_vent_class_id')->comment('primary key of trach_vent_classes');
            $table->integer('tracker_id')->comment('primary key of mssql_recruitingtracker');
            $table->string('outcome',100)->nullable();
            $table->boolean('is_deleted')->default(0);
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
        Schema::dropIfExists('trach_vent_class_request');
    }
}
