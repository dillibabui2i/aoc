<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns the "Hospitalization Record" form needs that the redesigned schema didn't carry: who opened
 * the tracker, plus three fields of its Current Hospitalization Info section.
 *
 * All nullable, like the rest of these tables: a tracker (and the encounter it hangs off) is created by
 * the header form, and the hospitalisation details are filled in afterwards, so nothing but the
 * encounter link can be demanded at insert time. That the section's fields must be answered is enforced
 * where the section is submitted (HospitalizationTrackerFieldEnum::isRequired), not by the column — a
 * NOT NULL hospitalization_type would need a DB default to survive encounter creation, and that default
 * would then read as a real answer the user never gave.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('hospitalization_trackers', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable()->after('encounter_id');
            // Same width as the table's other enum-backed columns (status, source, closing_reason).
            $table->string('declined_program_contact', 50)->nullable()->after('sent_to_review_by_id');
            $table->json('aoc_care_requirements')->nullable()->after('declined_program_contact');

            $table->foreign('author_id')->references('id')->on('users');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->string('hospitalization_type', 50)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('hospitalization_trackers', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropColumn(['author_id', 'declined_program_contact', 'aoc_care_requirements']);
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn('hospitalization_type');
        });
    }
};
