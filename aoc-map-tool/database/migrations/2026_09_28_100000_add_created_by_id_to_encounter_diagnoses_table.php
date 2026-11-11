<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('encounter_diagnoses', function (Blueprint $table) {
            // Who logged the diagnosis — always the current user at creation time, never a free-text
            // input and never changed on edit (mirrors HospitalizationTrackerContact's received_by_id).
            $table->unsignedBigInteger('created_by_id')->nullable()->after('encounter_id');

            $table->foreign('created_by_id', 'fk_ht_diagnoses_created_by_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('encounter_diagnoses', function (Blueprint $table) {
            $table->dropForeign('fk_ht_diagnoses_created_by_id');
            $table->dropColumn('created_by_id');
        });
    }
};
