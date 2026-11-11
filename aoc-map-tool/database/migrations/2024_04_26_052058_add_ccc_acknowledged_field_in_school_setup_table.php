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
            $table->boolean('ccc_acknowledged')->after('status_cancelled_reason')->default(0);
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
            $table->dropColumn('ccc_acknowledged');
        });
    }
};
