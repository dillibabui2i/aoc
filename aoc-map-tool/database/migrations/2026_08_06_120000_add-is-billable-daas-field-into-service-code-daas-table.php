<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_codes_daas', function (Blueprint $table) {
            $table->boolean('is_billable_daas')->default(0)->index();
        });
    }

    public function down()
    {
        Schema::table('service_codes_daas', function (Blueprint $table) {
            $table->dropColumn('is_billable_daas');
        });
    }
};
