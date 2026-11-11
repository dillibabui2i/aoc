<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->integer('intake_admission_visit_original')->nullable()->after('intake_first_billable_visit');
            $table->integer('intake_first_billable_visit_original')->nullable()->after('intake_admission_visit_original');
            $table->tinyInteger('intake_completed_original')->nullable()->after('intake_first_billable_visit_original');
        });

        DB::statement('UPDATE intake
            SET intake_admission_visit_original = intake_admission_visit,
                intake_first_billable_visit_original = intake_first_billable_visit,
                intake_completed_original = intake_completed');
    }

    public function down()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn([
                'intake_admission_visit_original',
                'intake_first_billable_visit_original',
                'intake_completed_original',
            ]);
        });
    }
};
