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
            $table->string('intake_nonadmit_reason')->nullable();
            $table->string('intake_controllable')->nullable();
            $table->boolean('intake_lead_exclude')->default(0)->nullable();
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
            $table->dropColumn('intake_nonadmit_reason');
            $table->dropColumn('intake_controllable');
            $table->dropColumn('intake_lead_exclude');
        });
    }
};
