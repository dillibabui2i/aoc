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
        Schema::table('intake', function (Blueprint $table) {
            $table->tinyInteger('intake_completed')->default(0)->nullable();
        });

        DB::statement('update intake left join mssql_patients on mssql_patients.id = intake.aoc_patient_id
                        set intake.intake_completed = 1 where intake_first_billable_visit is not null');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn('intake_completed');
        });
    }
};
