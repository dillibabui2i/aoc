<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('school_setup_requests', function (Blueprint $table) {
            $table->tinyInteger('is_deleted')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('school_setup_requests', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
            $table->dropColumn('deleted_at');
        });
    }
};
