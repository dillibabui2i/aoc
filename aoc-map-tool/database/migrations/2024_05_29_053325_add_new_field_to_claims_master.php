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
        Schema::table('claims_master', function (Blueprint $table) {
            $table->string('reason')->nullable();
            $table->string('comment')->nullable();
            $table->integer('followedup_by')->nullable();
            $table->datetime('followedup_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claims_master', function (Blueprint $table) {
            $table->dropColumn('reason');
            $table->dropColumn('comment');
            $table->dropColumn('followedup_by');
            $table->dropColumn('followedup_at');
        });
    }
};
