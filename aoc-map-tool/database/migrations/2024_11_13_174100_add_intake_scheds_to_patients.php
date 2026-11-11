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
            $table->integer('intake_planned_admission_visit')->nullable();
            $table->integer('intake_admission_visit')->nullable();
            $table->integer('intake_first_billable_visit')->nullable();
            
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
                'intake_planned_admission_visit',
                'intake_admission_visit',
                'intake_first_billable_visit'
            ]);
        });
    }
};
