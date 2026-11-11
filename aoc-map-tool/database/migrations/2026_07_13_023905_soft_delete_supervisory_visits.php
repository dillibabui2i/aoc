<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('supervisory_visits', function (Blueprint $table) {
            $table->tinyInteger('is_deleted')->default(0);
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('supervisory_visits', function (Blueprint $table) {
            $table->removeColumn('is_deleted');
            $table->removeColumn('deleted_at');
        });
    }
};
