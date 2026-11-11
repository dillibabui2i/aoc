<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'uq_imm_recommendations_vaccine_state';

    /**
     * One schedule template per vaccine and state. UniqueVaccinePerState checks this before saving; the index
     * is what holds when two saves race. Rows with a NULL state (federal) are not constrained by MySQL.
     */
    public function up(): void
    {
        Schema::table('immunization_recommendations', function (Blueprint $table) {
            $table->unique(['vaccine_cvx_code', 'originating_state'], self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('immunization_recommendations', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }
};
