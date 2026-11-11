<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('aoc_cf_city', 255)->nullable()->after('postalCode');
            $table->string('aoc_cf_state', 255)->nullable()->after('aoc_cf_city');
            $table->string('aoc_cf_mile_radius', 255)->nullable()->after('aoc_cf_state');
            $table->text('aoc_cf_schedule')->nullable()->after('aoc_cf_mile_radius');
            $table->text('aoc_cf_environment')->nullable()->after('aoc_cf_schedule');
            $table->text('aoc_cf_lifting_requirements')->nullable()->after('aoc_cf_environment');
            $table->text('aoc_cf_skills')->nullable()->after('aoc_cf_lifting_requirements');
            $table->string('aoc_cf_language', 255)->nullable()->after('aoc_cf_skills');
        });
    }

    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
              'aoc_cf_city',
              'aoc_cf_state',
              'aoc_cf_mile_radius',
              'aoc_cf_schedule',
              'aoc_cf_environment',
              'aoc_cf_lifting_requirements',
              'aoc_cf_skills',
              'aoc_cf_language',
            ]);
        });
    }
};
