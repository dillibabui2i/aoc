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
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->string('reasonNotBilled')->nullable();
            $table->datetime('reasonNotBilledCreatedAt')->nullable();
            $table->integer('reasonNotBilledAddedBy')->nullable()->comment('Users ID Ref');
        });
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->index('reasonNotBilledCreatedAt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->dropColumn('reasonNotBilled');
            $table->dropColumn('reasonNotBilledCreatedAt');
            $table->dropColumn('reasonNotBilledAddedBy');
        });
    }
};
