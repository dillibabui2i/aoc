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
        Schema::table('nth_inquiries', function (Blueprint $table) {
            $table->string('resolution')->nullable();
            $table->string('resolution_detail', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nth_inquiries', function (Blueprint $table) {
            $table->dropColumn('resolution');
            $table->dropColumn('resolution_detail');
        });
    }
};
