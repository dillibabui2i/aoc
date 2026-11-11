<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_codes_daas', function (Blueprint $table) {
            $table->integer('lobid')->nullable()->index();
        });

        Schema::table('service_codes_daas', function(Blueprint $table){
            $table->index(['service_id', 'is_admissions', 'lobid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_codes_daas', function (Blueprint $table) {
            $table->dropColumn('lobid');
        });

        Schema::table('service_codes_daas', function(Blueprint $table){
            $table->dropIndex(['service_id', 'is_admissions', 'lobid']);
        });
    }
};
