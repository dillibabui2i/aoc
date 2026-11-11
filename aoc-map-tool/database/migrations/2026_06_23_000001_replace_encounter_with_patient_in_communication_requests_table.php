<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('communication_requests', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
        });
        Schema::table('communication_requests', function (Blueprint $table) {
            $table->dropColumn('encounter_id');
            $table->unsignedBigInteger('patient_id')->after('id');
        });
        Schema::table('communication_requests', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('mssql_patients');
        });
    }

    public function down(): void
    {
        Schema::table('communication_requests', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });
        Schema::table('communication_requests', function (Blueprint $table) {
            $table->dropColumn('patient_id');
            $table->unsignedBigInteger('encounter_id')->nullable()->after('id');
        });
        Schema::table('communication_requests', function (Blueprint $table) {
            $table->foreign('encounter_id')->references('id')->on('encounters');
        });
    }
};
