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
        Schema::table('school_setup_requests', function (Blueprint $table) {
            $table->string('school_name', 255)->nullable()->after('tracker_id');
            $table->string('school_district', 255)->nullable()->after('school_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('school_setup_requests', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'school_district']);
        });
    }
};
