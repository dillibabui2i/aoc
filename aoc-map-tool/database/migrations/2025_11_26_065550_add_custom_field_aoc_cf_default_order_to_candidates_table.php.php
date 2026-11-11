<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('aoc_cf_default_order', 255)->nullable()->after('aoc_cf_language');
        });
    }


    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('aoc_cf_default_order');
        });
    }
};
