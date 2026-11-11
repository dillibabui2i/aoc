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
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->string('intake_bucket', 50)->nullable();
        });
		Schema::table('intake', function (Blueprint $table) {
            $table->string('intake_bucket', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->dropColumn([
                'intake_bucket'
            ]);
        });
		Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn([
                'intake_bucket'
            ]);
        });
    }
};
