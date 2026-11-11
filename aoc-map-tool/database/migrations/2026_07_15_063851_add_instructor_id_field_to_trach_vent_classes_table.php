<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->foreign('instructor_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->dropColumn('instructor');
        });
    }

    public function down()
    {
        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropColumn('instructor_id');
            $table->integer('instructor')->comment('primary key of users');
        });
    }
};
