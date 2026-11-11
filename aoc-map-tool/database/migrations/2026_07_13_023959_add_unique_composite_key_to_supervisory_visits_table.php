<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('supervisory_visits', function (Blueprint $table) {
            $table->unique(['aoc_provider_id', 'aoc_patient_id', 'visit_date_due', 'last_visit', 'is_deleted'], 'uq_sv_a_provider_patient_visit_status');
            $table->index(['aoc_provider_id', 'aoc_patient_id', 'visit_date_due', 'last_visit'], 'uq_sv_a_provider_patient_visit');
        });
    }

    public function down()
    {
        Schema::table('supervisory_visits', function (Blueprint $table) {
            $table->dropIndex('uq_sv_a_provider_patient_visit');
            $table->dropUnique('uq_sv_a_provider_patient_visit_status');
        });
    }
};
